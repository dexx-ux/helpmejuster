<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class UserNotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:user');
    }
    
    public function index(Request $request)
    {
        $user = $request->user();
        
        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        $unreadCount = $user->unreadNotifications()->count();
        $readCount = $user->notifications()->whereNotNull('read_at')->count();
        
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ]);
        }

        return view('user.notifications.index', compact('notifications', 'unreadCount', 'readCount'));
    }

    public function show(Request $request, DatabaseNotification $notification)
    {
        $user = $request->user();

        // Verify ownership
        if ($notification->notifiable_type !== get_class($user) || $notification->notifiable_id !== $user->getKey()) {
            abort(403);
        }

        $data = $notification->data ?? [];
        
        // Check if this is a deleted notification
        $isDeleted = data_get($data, 'type') === 'deleted' || 
                     data_get($data, 'is_deleted') === true ||
                     !empty(data_get($data, 'deleted_by'));
        
        // Mark as read if unread (for all notifications)
        if (!$notification->read_at) {
            $notification->markAsRead();
        }
        
        // If deleted notification, just show the view without redirecting
        if ($isDeleted) {
            return view('user.notifications.show', [
                'notification' => $notification,
                'data' => $data,
                'warning' => 'This item has been deleted and cannot be accessed.',
                'isDeleted' => true
            ]);
        }
        
        // For non-deleted notifications, redirect to the appropriate resource
        $ticketId = data_get($data, 'ticket_id');
        
        // Check if the resource actually exists before redirecting
        if ($ticketId) {
            $ticket = Ticket::find($ticketId);
            if ($ticket) {
                return redirect()->route('user.tickets.show', $ticketId);
            } else {
                // Ticket doesn't exist, treat as deleted
                return view('user.notifications.show', [
                    'notification' => $notification,
                    'data' => $data,
                    'warning' => 'The ticket associated with this notification no longer exists.',
                    'isDeleted' => true
                ]);
            }
        }
        
        // Fallback for notifications without specific resource
        return view('user.notifications.show', [
            'notification' => $notification,
            'data' => $data,
            'warning' => null,
            'isDeleted' => false
        ]);
    }
    
    /**
     * Mark a single notification as read via AJAX
     */
    public function markAsRead(Request $request, DatabaseNotification $notification)
    {
        $user = $request->user();
        
        // Verify ownership
        if ($notification->notifiable_type !== get_class($user) || $notification->notifiable_id !== $user->getKey()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Mark as read if not already
        if (!$notification->read_at) {
            $notification->markAsRead();
        }
        
        // Check if this is a deleted notification
        $data = $notification->data ?? [];
        $isDeleted = data_get($data, 'type') === 'deleted' || 
                     data_get($data, 'is_deleted') === true ||
                     !empty(data_get($data, 'deleted_by'));
        
        // Determine redirect URL for non-deleted notifications
        $redirectUrl = null;
        if (!$isDeleted) {
            $ticketId = data_get($data, 'ticket_id');
            
            if ($ticketId) {
                $ticket = Ticket::find($ticketId);
                if ($ticket) {
                    $redirectUrl = route('user.tickets.show', $ticketId);
                } else {
                    // Ticket doesn't exist, mark as deleted
                    $isDeleted = true;
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'redirect_url' => $redirectUrl,
            'is_deleted' => $isDeleted,
            'notification_url' => route('user.notifications.show', $notification->id)
        ]);
    }
    
    /**
     * Get unread count for AJAX requests
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();
        return response()->json(['unread_count' => $count]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return redirect()->route('user.notifications')->with('success', 'All notifications marked as read.');
    }

    public function deleteAll(Request $request)
    {
        $request->user()->notifications()->whereNotNull('read_at')->delete();
        
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return redirect()->route('user.notifications')->with('success', 'All read notifications cleared.');
    }
}