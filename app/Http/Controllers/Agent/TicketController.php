<?php

namespace App\Http\Controllers\Agent;

use App\Models\Attachment;
use App\Models\Category;
use App\Events\TicketAssigned;
use App\Events\TicketStatusChanged;
use App\Models\Comment;
use App\Models\MessageNotification;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketLog;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Traits\CommentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketController extends \App\Http\Controllers\Controller
{
    use CommentManager;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:agent');
    }

 public function index(Request $request)
{
    $query = Ticket::with(['category', 'user', 'assignedTo'])
        ->where('assigned_to', Auth::id());

    if ($request->filled('status')) {
        if ($request->status === 'pending') {
            $query->whereIn('status', [
                Ticket::STATUS_PENDING,
                Ticket::STATUS_PENDING_USER_RESPONSE,
                Ticket::STATUS_PENDING_ADMIN_APPROVAL,
            ]);
        } else {
            $query->where('status', $request->status);
        }
    }
    
    if ($request->filled('category')) {
        $query->where('category_id', $request->category);
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('ticket_number', 'like', "%{$search}%")
              ->orWhere('title', 'like', "%{$search}%")
              ->orWhereHas('user', function($q2) use ($search) {
                  $q2->where('name', 'like', "%{$search}%");
              });
        });
    }

    $tickets = $query->orderBy('created_at', 'desc')->paginate(10);

    // Calculate statistics for stats cards
    $totalAssigned = Ticket::where('assigned_to', Auth::id())->count();
    $openCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])->count();
    $pendingCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_PENDING, Ticket::STATUS_PENDING_USER_RESPONSE, Ticket::STATUS_PENDING_ADMIN_APPROVAL])->count();
    $inProgressCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_IN_PROGRESS)->count();
    $resolvedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_RESOLVED)->count();
    $closedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_CLOSED)->count();
    $canceledCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_CANCELED)->count();

    // Get categories for filter dropdown
    $categories = Category::all();

    // For AJAX requests, return JSON
    if ($request->ajax() || $request->has('ajax')) {
        $html = view('agent.tickets.partials.table', compact('tickets'))->render();
        
        return response()->json([
            'html' => $html,
            'results_count' => "Showing " . ($tickets->firstItem() ?? 0) . " to " . ($tickets->lastItem() ?? 0) . " of " . $tickets->total() . " tickets",
            'stats' => [
                'total' => $totalAssigned,
                'open' => $openCount,
                'pending' => $pendingCount,
                'in_progress' => $inProgressCount,
                'resolved' => $resolvedCount,
                'closed' => $closedCount,
                'canceled' => $canceledCount,
            ]
        ]);
    }

    return view('agent.tickets.index', compact('tickets', 'categories', 'totalAssigned', 'openCount', 'pendingCount', 'inProgressCount', 'resolvedCount', 'closedCount', 'canceledCount'));
}

    public function create()
    {
        $categories = Category::all();
        $users = User::where('role', 'user')->get();

        return view('agent.tickets.create', compact('categories', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category_id' => 'required|exists:categories,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
        ]);

        $ticket = Ticket::create([
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'category_id' => $validated['category_id'],
            'department_id' => Category::find($validated['category_id'])->department_id,
            'assigned_to' => Auth::id(),
            'user_id' => $validated['user_id'],
            'status' => Ticket::STATUS_IN_PROGRESS,
            'ticket_number' => 'TKT-' . strtoupper(uniqid()),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets/' . $ticket->id, 'public');
                $ticket->attachments()->create([
                    'filename' => $file->getClientOriginalName(),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'path' => $path,
                    'user_id' => Auth::id(),
                ]);
            }
        }

        if ($ticket->user_id !== Auth::id()) {
            $ticket->user->notify(new NewTicketNotification($ticket));
        }

        return redirect()->route('agent.tickets.show', $ticket)
            ->with('success', 'Ticket created successfully!');
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        if (Auth::user()->isAdmin() || Auth::user()->isAgent()) {
            $ticket->load(['category', 'assignedTo', 'user', 'comments.user', 'comments.attachments', 'attachments']);
        } else {
            $ticket->load(['category', 'assignedTo', 'user', 'attachments']);
            $ticket->setRelation('comments', $ticket->comments()->where('is_internal', false)->with(['user', 'attachments'])->orderBy('created_at')->get());
        }
        $categories = Category::all();
        $agents = User::where('role', 'agent')->get();

        return view('agent.tickets.show', compact('ticket', 'categories', 'agents'));
    }

    public function comment(Request $request, Ticket $ticket)
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate([
            'content' => 'required|string',
            'is_internal' => 'sometimes|boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
        ]);

        $comment = $ticket->comments()->create([
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'is_internal' => $request->has('is_internal') ? true : false,
        ]);

        // Generate unique message anchor
        $comment->update(['message_anchor' => $this->generateMessageAnchor($comment)]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets/' . $ticket->id, 'public');
                \App\Models\Attachment::create([
                    'ticket_id' => $ticket->id,
                    'comment_id' => $comment->id,
                    'filename' => $file->getClientOriginalName(),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'path' => $path,
                    'user_id' => Auth::id(),
                ]);
            }
        }

        $oldStatus = $ticket->status;
        $newStatus = $oldStatus;

        if (in_array($ticket->status, [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED], true)) {
            $newStatus = Ticket::STATUS_IN_PROGRESS;
            $ticket->update(['status' => $newStatus]);
        }

        $ticket->logs()->create([
            'user_id' => Auth::id(),
            'action' => 'comment_added',
            'metadata' => [
                'comment_id' => $comment->id,
                'internal' => $comment->is_internal,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
        ]);

        if ($oldStatus !== $newStatus) {
            event(new TicketStatusChanged($ticket, $oldStatus, $newStatus));
        }

        // Notify ticket user (if not internal)
        if (!$comment->is_internal && $ticket->user) {
            $this->createMessageNotification(
                $ticket->user,
                $ticket,
                $comment,
                Auth::user(),
                'new_message',
                Auth::user()->name . ' replied to ticket #' . $ticket->ticket_number
            );
        }

        // Notify other agents and admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            if ($admin->id !== Auth::id()) {
                $this->createMessageNotification(
                    $admin,
                    $ticket,
                    $comment,
                    Auth::user(),
                    'new_message',
                    Auth::user()->name . ' replied to ticket #' . $ticket->ticket_number
                );
            }
        }

        return back()->with('success', 'Comment added successfully!');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', [
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_PENDING,
                Ticket::STATUS_PENDING_USER_RESPONSE,
                Ticket::STATUS_PENDING_ADMIN_APPROVAL,
                Ticket::STATUS_ESCALATED,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
                Ticket::STATUS_CANCELED,
            ]),
        ]);

        $oldStatus = $ticket->status;
        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === Ticket::STATUS_RESOLVED) {
            $updateData['resolved_at'] = now();
        } elseif ($validated['status'] === Ticket::STATUS_CLOSED) {
            $updateData['closed_at'] = now();
        }

        $ticket->update($updateData);

        $ticket->logs()->create([
            'user_id' => Auth::id(),
            'action' => 'status_updated',
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $ticket->status,
            ],
        ]);

        if ($oldStatus !== $ticket->status) {
            event(new TicketStatusChanged($ticket, $oldStatus, $ticket->status));
        }

        return back()->with('success', 'Ticket status updated successfully!');
    }

    public function downloadAttachment(Attachment $attachment)
    {
        $ticket = $attachment->ticket;

        if (Auth::id() !== $ticket->assigned_to) {
            abort(403);
        }

        $filePath = storage_path('app/public/' . $attachment->path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->file($filePath, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
        ]);
    }

    public function assigned()
    {
        $tickets = Ticket::with(['category', 'user', 'assignedTo'])
            ->where('assigned_to', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Calculate statistics for stats cards
        $totalAssigned = Ticket::where('assigned_to', Auth::id())->count();
        $openCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])->count();
        $inProgressCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_IN_PROGRESS)->count();
        $pendingCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_PENDING_USER_RESPONSE, Ticket::STATUS_PENDING_ADMIN_APPROVAL])->count();
        $resolvedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_RESOLVED)->count();
        $closedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_CLOSED)->count();
        
        // Get categories for filter dropdown
        $categories = Category::all();

        return view('agent.tickets.index', compact('tickets', 'categories', 'totalAssigned', 'openCount', 'inProgressCount', 'pendingCount', 'resolvedCount', 'closedCount'));
    }

    public function inProgress()
    {
        $tickets = Ticket::with(['category', 'user', 'assignedTo'])
            ->where('assigned_to', Auth::id())
            ->where('status', Ticket::STATUS_IN_PROGRESS)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        $totalAssigned = Ticket::where('assigned_to', Auth::id())->count();
        $openCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])->count();
        $inProgressCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_IN_PROGRESS)->count();
        $pendingCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_PENDING_USER_RESPONSE, Ticket::STATUS_PENDING_ADMIN_APPROVAL])->count();
        $resolvedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_RESOLVED)->count();
        $closedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_CLOSED)->count();
        $canceledCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_CANCELED)->count();
        $categories = Category::all();

        return view('agent.tickets.index', compact('tickets', 'categories', 'totalAssigned', 'openCount', 'inProgressCount', 'pendingCount', 'resolvedCount', 'closedCount', 'canceledCount'));
    }

    public function resolved()
    {
        $tickets = Ticket::with(['category', 'user', 'assignedTo'])
            ->where('assigned_to', Auth::id())
            ->where('status', Ticket::STATUS_RESOLVED)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Calculate statistics for stats cards
        $totalAssigned = Ticket::where('assigned_to', Auth::id())->count();
        $openCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])->count();
        $inProgressCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_IN_PROGRESS)->count();
        $pendingCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_PENDING_USER_RESPONSE, Ticket::STATUS_PENDING_ADMIN_APPROVAL])->count();
        $resolvedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_RESOLVED)->count();
        $closedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_CLOSED)->count();
        $canceledCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_CANCELED)->count();
        
        // Get categories for filter dropdown
        $categories = Category::all();

        return view('agent.tickets.index', compact('tickets', 'categories', 'totalAssigned', 'openCount', 'inProgressCount', 'pendingCount', 'resolvedCount', 'closedCount', 'canceledCount'));
    }

    public function closed()
    {
        $tickets = Ticket::with(['category', 'user', 'assignedTo'])
            ->where('assigned_to', Auth::id())
            ->where('status', Ticket::STATUS_CLOSED)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Calculate statistics for stats cards
        $totalAssigned = Ticket::where('assigned_to', Auth::id())->count();
        $openCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])->count();
        $inProgressCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_IN_PROGRESS)->count();
        $pendingCount = Ticket::where('assigned_to', Auth::id())->whereIn('status', [Ticket::STATUS_PENDING_USER_RESPONSE, Ticket::STATUS_PENDING_ADMIN_APPROVAL])->count();
        $resolvedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_RESOLVED)->count();
        $closedCount = Ticket::where('assigned_to', Auth::id())->where('status', Ticket::STATUS_CLOSED)->count();
        
        // Get categories for filter dropdown
        $categories = Category::all();

        return view('agent.tickets.index', compact('tickets', 'categories', 'totalAssigned', 'openCount', 'inProgressCount', 'pendingCount', 'resolvedCount', 'closedCount'));
    }

    /**
     * Delete a comment (AJAX endpoint)
     */
    public function deleteComment(Request $request, Comment $comment)
    {
        $ticket = $comment->ticket;
        
        // Check if comment exists and user can delete it
        if (!$comment || !$comment->canBeDeletedBy(Auth::user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$this->performCommentDeletion($comment, Auth::user())) {
            return response()->json(['success' => false, 'message' => 'Unable to delete comment'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
            'comment_id' => $comment->id,
        ]);
    }

    /**
     * Get new/updated comments for real-time refresh (AJAX endpoint)
     */
    public function refreshComments(Request $request, Ticket $ticket)
    {
        $this->authorize('reply', $ticket);

        $lastCheckedAt = $request->input('last_checked_at');
        $newComments = $this->getNewComments($ticket, $lastCheckedAt);

        return response()->json([
            'success' => true,
            'comments' => $newComments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->trashed() ? 'This message was deleted' : $comment->content,
                    'is_deleted' => $comment->trashed(),
                    'user_name' => $comment->user->name,
                    'user_avatar' => $comment->user->avatar_url,
                    'created_at' => $comment->created_at->format('M d, h:i A'),
                    'message_anchor' => $comment->message_anchor,
                    'is_internal' => $comment->is_internal,
                    'attachments' => $comment->attachments->map(fn($a) => [
                        'id' => $a->id,
                        'name' => $a->original_name,
                        'path' => route('agent.tickets.download', $a),
                    ]),
                ];
            }),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Mark message notification as read (AJAX endpoint)
     */
    public function markNotificationRead(Request $request, Comment $comment)
    {
        $this->markCommentNotificationAsRead($comment, Auth::user());
        
        return response()->json([
            'success' => true,
            'unread_count' => $this->getUnreadNotificationCount(Auth::user()),
        ]);
    }

    /**
     * Get unread notification count (AJAX endpoint)
     */
    public function getUnreadCount()
    {
        return response()->json([
            'unread_count' => $this->getUnreadNotificationCount(Auth::user()),
        ]);
    }
}
