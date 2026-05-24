<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Api\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\TicketController as ApiTicketController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\AgentApplicationController;
use App\Models\Ticket;
use App\Models\User;

// ========== IMPORT NEW CONTROLLERS ==========
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AgentUserController;
use App\Http\Controllers\Admin\EndUserController;
use App\Http\Controllers\Agent\ProfileController as AgentProfileController;

// ========== PUBLIC ROUTES ==========
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/welcome/stats', function () {
    return response()->json([
        'total_tickets' => Ticket::count(),
        'open_tickets' => Ticket::whereIn('status', ['open', 'in_progress', 'pending'])->count(),
        'resolved_tickets' => Ticket::where('status', 'resolved')->count(),
        'total_users' => User::where('status', 'active')->count(),
    ]);
})->name('welcome.stats');

// Agent Application Routes
Route::get('/apply/agent', [AgentApplicationController::class, 'showForm'])->name('agent.apply');
Route::post('/apply/agent', [AgentApplicationController::class, 'submit'])->name('agent.application.submit');
Route::get('/apply/success', [AgentApplicationController::class, 'success'])->name('application.success');

// Application Status Check Routes
Route::get('/check-application-status', [App\Http\Controllers\ApplicationStatusController::class, 'showForm'])->name('application.status.form');
Route::post('/check-application-status', [App\Http\Controllers\ApplicationStatusController::class, 'check'])->name('application.status.check');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password reset routes
    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// ========== API ROUTES ==========
Route::prefix('v1')->group(function () {
    Route::get('/categories', [ApiCategoryController::class, 'index']);
    Route::post('/tickets', [ApiTicketController::class, 'store']);
    Route::get('/tickets/{ticket_number}', [ApiTicketController::class, 'show']);
    Route::post('/tickets/{ticket_number}/responses', [ApiTicketController::class, 'addResponse']);
});

// ========== AUTHENTICATED ROUTES ==========
Route::middleware('auth')->group(function () {
    // Email verification routes (code-based)
    Route::get('/email/verify', [VerificationController::class, 'showCodeForm'])->name('verification.notice');
    Route::post('/email/verify-code', [VerificationController::class, 'verifyCode'])->name('verification.verify-code');
    Route::post('/email/resend', [VerificationController::class, 'resendCode'])->name('verification.resend');

    // Password confirmation routes
    Route::get('/password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
    Route::post('/password/confirm', [ConfirmPasswordController::class, 'confirm'])->name('password.confirm.store');
});

// ========== AUTHENTICATED USER ROUTES ==========
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
  
    // Profile redirector based on user role
    Route::get('/profile', function () {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.profile');
        } elseif ($user->hasRole('agent')) {
            return redirect()->route('agent.profile'); // If agent has profile
        } else {
            return redirect()->route('user.profile');
        }
    })->name('profile.redirect');

    // Profile password redirector
    Route::get('/profile/password', function () {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.profile.password');
        } elseif ($user->hasRole('agent')) {
            return redirect()->route('agent.profile.password'); // If agent has
        } else {
            return redirect()->route('user.profile.password');
        }
    })->name('profile.password.redirect');

    // Dashboard redirector
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // ========== ADMIN DASHBOARD ==========
    Route::get('/admin/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/inadmin', function () {
        return redirect()->route('admin.dashboard');
    })->name('inadmin');
    
    // ========== USER DASHBOARD ==========
    Route::get('/user/dashboard', [App\Http\Controllers\User\DashboardController::class, 'index'])->name('user.dashboard');
    
    // ========== USER TICKET ROUTES ==========
    Route::prefix('user/tickets')->name('user.tickets.')->group(function () {
        Route::get('/', [App\Http\Controllers\User\TicketController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\User\TicketController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\User\TicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [App\Http\Controllers\User\TicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [App\Http\Controllers\User\TicketController::class, 'edit'])->name('edit');
        Route::put('/{ticket}', [App\Http\Controllers\User\TicketController::class, 'update'])->name('update');
        Route::delete('/{ticket}', [App\Http\Controllers\User\TicketController::class, 'destroy'])->name('destroy');
        Route::post('/{ticket}/comment', [App\Http\Controllers\User\TicketController::class, 'comment'])->name('comment');
        Route::patch('/{ticket}/status', [App\Http\Controllers\User\TicketController::class, 'updateStatus'])->name('update-status');
        Route::post('/{ticket}/cancel', [App\Http\Controllers\User\TicketController::class, 'cancel'])->name('cancel');
        Route::post('/{ticket}/rate-agent', [App\Http\Controllers\User\TicketController::class, 'rateAgent'])->name('rate-agent');
        Route::get('/attachment/{attachment}/download', [App\Http\Controllers\User\TicketController::class, 'downloadAttachment'])->name('download');
        Route::get('/export/csv', [App\Http\Controllers\User\TicketController::class, 'exportCSV'])->name('export-csv');
        // Real-time and message management routes
        Route::delete('/comment/{comment}', [App\Http\Controllers\User\TicketController::class, 'deleteComment'])->name('comment.delete');
        Route::post('/{ticket}/refresh-comments', [App\Http\Controllers\User\TicketController::class, 'refreshComments'])->name('refresh-comments');
        Route::post('/comment/{comment}/mark-read', [App\Http\Controllers\User\TicketController::class, 'markNotificationRead'])->name('comment.mark-read');
        Route::get('/unread-count', [App\Http\Controllers\User\TicketController::class, 'getUnreadCount'])->name('unread-count');
    });

    // ========== USER OTHER ROUTES ==========
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/agents', [App\Http\Controllers\User\AgentController::class, 'index'])->name('agents');
        Route::get('/agents/{agent}', [App\Http\Controllers\User\AgentController::class, 'show'])->name('agents.show');
        // KnowledgeBase routes removed
        Route::get('/support', [App\Http\Controllers\User\SupportController::class, 'index'])->name('support');
        Route::get('/ratings', [App\Http\Controllers\User\RatingController::class, 'index'])->name('ratings');
        Route::get('/ratings/{rating}/edit', [App\Http\Controllers\User\RatingController::class, 'edit'])->name('ratings.edit');
        Route::put('/ratings/{rating}', [App\Http\Controllers\User\RatingController::class, 'update'])->name('ratings.update');
        Route::delete('/ratings/{rating}', [App\Http\Controllers\User\RatingController::class, 'destroy'])->name('ratings.destroy');


         // ========== USER NOTIFICATIONS ==========
 Route::get('/notifications', [App\Http\Controllers\User\UserNotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/{notification}', [App\Http\Controllers\User\UserNotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\User\UserNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/delete-all', [App\Http\Controllers\User\UserNotificationController::class, 'deleteAll'])->name('notifications.delete-all');
    Route::post('/notifications/{notification}/mark-read', [App\Http\Controllers\User\UserNotificationController::class, 'markAsRead'])->name('notifications.mark-read-ajax');
    Route::get('/notifications/unread-count', [App\Http\Controllers\User\UserNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    
        // ========== USER PROFILE ROUTES ==========
        Route::get('/profile', [App\Http\Controllers\User\ProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [App\Http\Controllers\User\ProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/password', [App\Http\Controllers\User\ProfileController::class, 'password'])->name('profile.password');
        Route::put('/profile/password', [App\Http\Controllers\User\ProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::put('/profile/preferences', [App\Http\Controllers\User\ProfileController::class, 'updatePreferences'])->name('profile.preferences');
        Route::post('/profile/avatar', [App\Http\Controllers\User\ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [App\Http\Controllers\User\ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
        Route::delete('/profile/delete', [App\Http\Controllers\User\ProfileController::class, 'deleteAccount'])->name('profile.delete');
        Route::get('/profile/export', [App\Http\Controllers\User\ProfileController::class, 'exportData'])->name('profile.export');
        Route::get('/profile/activities', [App\Http\Controllers\User\ProfileController::class, 'getActivityLog'])->name('profile.activities');
    });

    Route::prefix('agent')->name('agent.')->middleware('role:agent')->group(function () {
              Route::get('/dashboard', [App\Http\Controllers\Agent\DashboardController::class, 'index'])->name('dashboard');

              // ========== AGENT NOTIFICATIONS ==========
            Route::get('/notifications', [App\Http\Controllers\Agent\AgentNotificationController::class, 'index'])->name('notifications');
            Route::get('/notifications/{notification}', [App\Http\Controllers\Agent\AgentNotificationController::class, 'show'])->name('notifications.show');
            Route::post('/notifications/mark-all-read', [App\Http\Controllers\Agent\AgentNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
            Route::post('/notifications/delete-all', [App\Http\Controllers\Agent\AgentNotificationController::class, 'deleteAll'])->name('notifications.delete-all');

            Route::prefix('tickets')->name('tickets.')->group(function () {
            // Literal routes must come before parametric routes
            Route::get('/create', [App\Http\Controllers\Agent\TicketController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Agent\TicketController::class, 'store'])->name('store');
            Route::get('/assigned', [App\Http\Controllers\Agent\TicketController::class, 'assigned'])->name('assigned');
            Route::get('/in-progress', [App\Http\Controllers\Agent\TicketController::class, 'inProgress'])->name('in-progress');
            Route::get('/resolved', [App\Http\Controllers\Agent\TicketController::class, 'resolved'])->name('resolved');
            Route::get('/closed', [App\Http\Controllers\Agent\TicketController::class, 'closed'])->name('closed');
<<<<<<< HEAD
        // Parametric routes
=======
            
                        

            // Parametric routes
>>>>>>> b0c0e2c971a5c46df7f3dd5eab2ca3a7e70516f3
            Route::get('/attachment/{attachment}/download', [App\Http\Controllers\Agent\TicketController::class, 'downloadAttachment'])->name('download');
            Route::post('/{ticket}/comment', [App\Http\Controllers\Agent\TicketController::class, 'comment'])->name('comment');
            Route::patch('/{ticket}/status', [App\Http\Controllers\Agent\TicketController::class, 'updateStatus'])->name('update-status');
            Route::get('/{ticket}', [App\Http\Controllers\Agent\TicketController::class, 'show'])->name('show');
            // Real-time and message management routes
            Route::delete('/comment/{comment}', [App\Http\Controllers\Agent\TicketController::class, 'deleteComment'])->name('comment.delete');
            Route::post('/{ticket}/refresh-comments', [App\Http\Controllers\Agent\TicketController::class, 'refreshComments'])->name('refresh-comments');
            Route::post('/comment/{comment}/mark-read', [App\Http\Controllers\Agent\TicketController::class, 'markNotificationRead'])->name('comment.mark-read');
            Route::get('/unread-count', [App\Http\Controllers\Agent\TicketController::class, 'getUnreadCount'])->name('unread-count');
            
            // Index route last
            Route::get('/', [App\Http\Controllers\Agent\TicketController::class, 'index'])->name('index');
        });

                                // ========== AGENT NOTIFICATIONS ==========
    Route::get('/notifications', [App\Http\Controllers\Agent\AgentNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [App\Http\Controllers\Agent\AgentNotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\Agent\AgentNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/delete-all', [App\Http\Controllers\Agent\AgentNotificationController::class, 'deleteAll'])->name('notifications.delete-all');
    Route::post('/notifications/{notification}/mark-read', [App\Http\Controllers\Agent\AgentNotificationController::class, 'markAsRead'])->name('notifications.mark-read-ajax');
    Route::get('/notifications/unread-count', [App\Http\Controllers\Agent\AgentNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
      

        // ========== AGENT PROFILE ROUTES ==========
        Route::get('/profile', [AgentProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [AgentProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/password', [AgentProfileController::class, 'password'])->name('profile.password');
        Route::put('/profile/password', [AgentProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::post('/profile/avatar', [AgentProfileController::class, 'uploadAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [AgentProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');


    
    });
    
    // ========== ADMIN ROUTES GROUP ==========
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        
        // ========== USER MANAGEMENT ROUTES ==========
        // All Users (Main CRUD using UserController)
        Route::delete('/users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::post('/users/bulk-destroy', [UserController::class, 'bulkDestroy']);
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
      
        // Agents Tab (route name: admin.users.agents)
        Route::get('/users/agents', [AgentUserController::class, 'index'])->name('users.agents');
        
            // ========== ADMIN NOTIFICATIONS ==========
<<<<<<< HEAD
         Route::get('/notifications', [App\Http\Controllers\Admin\AdminNotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/{notification}', [App\Http\Controllers\Admin\AdminNotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [App\Http\Controllers\Admin\AdminNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/delete-all', [App\Http\Controllers\Admin\AdminNotificationController::class, 'deleteAll'])->name('notifications.delete-all');
    
    // FIXED: AJAX routes for notifications
    Route::post('/notifications/{notification}/mark-read', [App\Http\Controllers\Admin\AdminNotificationController::class, 'markAsRead'])
        ->name('notifications.mark-read-ajax');
    
    Route::get('/notifications/unread-count', [App\Http\Controllers\Admin\AdminNotificationController::class, 'unreadCount'])
        ->name('notifications.unread-count');
=======
        Route::get('/notifications', [App\Http\Controllers\Admin\AdminNotificationController::class, 'index'])->name('notifications');
        Route::get('/notifications/{notification}', [App\Http\Controllers\Admin\AdminNotificationController::class, 'show'])->name('notifications.show');
        Route::post('/notifications/mark-all-read', [App\Http\Controllers\Admin\AdminNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::post('/notifications/delete-all', [App\Http\Controllers\Admin\AdminNotificationController::class, 'deleteAll'])->name('notifications.delete-all');
        // Mark single notification as read via AJAX
        Route::post('/notifications/{id}/mark-read', function ($id) {
            $notification = auth()->user()->notifications()->find($id);
            if ($notification && !$notification->read_at) {
            $notification->markAsRead();}return response()->json(['success' => true]);})->name('admin.notifications.mark-read-ajax');

        // Get unread count for AJAX
        Route::get('/notifications/unread-count', function () { return response()->json(['unread_count' => auth()->user()->unreadNotifications()->count()]);})->name('admin.notifications.unread-count');
>>>>>>> b0c0e2c971a5c46df7f3dd5eab2ca3a7e70516f3


        // Agent Features
        Route::get('/users/agents/assignments', [AgentUserController::class, 'assignments'])->name('users.agents.assignments');
        Route::get('/users/agents/performance', [AgentUserController::class, 'performance'])->name('users.agents.performance');
        Route::get('/users/agents/schedule', [AgentUserController::class, 'schedule'])->name('users.agents.schedule');
        Route::get('/users/agents/team-view', [AgentUserController::class, 'teamView'])->name('users.agents.team-view');
        
        // Pending Agent Registrations (route name: admin.users.pending-agents)
        Route::get('/users/pending-agents', [UserController::class, 'pendingAgents'])->name('users.pending-agents');
        Route::post('/users/{user}/approve-pending-agent', [UserController::class, 'approvePendingAgent'])->name('users.approve-pending-agent');
        Route::post('/users/{user}/reject-pending-agent', [UserController::class, 'rejectPendingAgent'])->name('users.reject-pending-agent');
        
        // End Users Tab (route name: admin.users.end-users)
        Route::get('/users/end-users', [EndUserController::class, 'index'])->name('users.end-users');
        
        // End User Features
        Route::get('/users/end-users/ticket-history/{user?}', [EndUserController::class, 'ticketHistory'])->name('users.end-users.ticket-history');
        Route::get('/users/end-users/feedback', [EndUserController::class, 'feedback'])->name('users.end-users.feedback');
        Route::get('/users/end-users/activity/{user?}', [EndUserController::class, 'activity'])->name('users.end-users.activity');
        Route::get('/users/end-users/support-view', [EndUserController::class, 'supportView'])->name('users.end-users.support-view');

        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::delete('/users/{user}/ajax', [UserController::class, 'destroyAjax'])->name('users.destroy-ajax');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');

        // ========== DEPARTMENT ROUTES ==========
        Route::delete('/departments/bulk-destroy', [DepartmentController::class, 'bulkDestroy'])->name('departments.bulk-destroy');
        Route::post('/departments/bulk-destroy', [DepartmentController::class, 'bulkDestroy']);
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/{id}', [DepartmentController::class, 'show'])->name('departments.show');
        Route::get('/departments/{id}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('/departments/{id}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
        Route::delete('/departments/{id}/ajax', [DepartmentController::class, 'destroyAjax'])->name('departments.destroy-ajax');
        Route::post('/departments/{id}/toggle', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle');
        // Return specializations for a department (used by admin modals via fetch)
        Route::get('/departments/{id}/specializations', [DepartmentController::class, 'specializations'])->name('departments.specializations');
        // Generate a unique employee ID for a department (used by admin modals)
        Route::get('/departments/{id}/generate-employee-id', [DepartmentController::class, 'generateEmployeeId'])->name('departments.generate-employee-id');

        // ========== CATEGORY ROUTES ==========
                Route::get('/categories/by-department', [CategoryController::class, 'getByDepartment'])->name('admin.categories.by-department');
        Route::delete('/categories/bulk-destroy', [CategoryController::class, 'bulkDestroy'])->name('categories.bulk-destroy');
        Route::post('/categories/bulk-destroy', [CategoryController::class, 'bulkDestroy']);
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/{id}/toggle', [CategoryController::class, 'toggleStatus'])->name('categories.toggle');

        // Admin knowledgebase routes removed

        // ========== ADMIN PROFILE ROUTES ==========
        Route::get('/profile', [App\Http\Controllers\Admin\ProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/password', [App\Http\Controllers\Admin\ProfileController::class, 'password'])->name('profile.password');
        Route::put('/profile/password', [App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::post('/profile/avatar', [App\Http\Controllers\Admin\ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [App\Http\Controllers\Admin\ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');

        // ========== ADMIN APPLICATION ROUTES ==========
        Route::get('/applications', [AgentApplicationController::class, 'index'])->name('applications');
        Route::get('/applications/{application}', [AgentApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/view/{fileType}', [AgentApplicationController::class, 'viewFile'])->name('applications.view');
        Route::get('/applications/{application}/download/{fileType}', [AgentApplicationController::class, 'downloadFile'])->name('applications.download');
        Route::post('/applications/{application}/approve', [AgentApplicationController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/reject', [AgentApplicationController::class, 'reject'])->name('applications.reject');
        Route::post('/applications/cleanup-orphaned', [AgentApplicationController::class, 'cleanupOrphaned'])->name('applications.cleanup-orphaned');
        Route::delete('/applications/bulk-destroy', [AgentApplicationController::class, 'bulkDestroy'])->name('applications.bulk-destroy');
        Route::delete('/applications/{application}', [AgentApplicationController::class, 'destroy'])->name('applications.destroy');
        
        // ========== ADMIN TICKET ROUTES ==========
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\TicketController::class, 'all'])->name('all');
            Route::get('/open', [App\Http\Controllers\Admin\TicketController::class, 'open'])->name('open');
            Route::get('/in-progress', [App\Http\Controllers\Admin\TicketController::class, 'inProgress'])->name('in-progress');
            Route::get('/resolved', [App\Http\Controllers\Admin\TicketController::class, 'resolved'])->name('resolved');
            Route::get('/closed', [App\Http\Controllers\Admin\TicketController::class, 'closed'])->name('closed');
            Route::get('/assigned-to-me', [App\Http\Controllers\Admin\TicketController::class, 'assignedToMe'])->name('assigned-to-me');
            Route::get('/assigned-by-me', [App\Http\Controllers\Admin\TicketController::class, 'assignedByMe'])->name('assigned-by-me');
            Route::get('/create', [App\Http\Controllers\Admin\TicketController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\TicketController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Admin\TicketController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Admin\TicketController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Admin\TicketController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Admin\TicketController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/comment', [App\Http\Controllers\Admin\TicketController::class, 'comment'])->name('comment');
            Route::post('/{id}/reply', [App\Http\Controllers\Admin\TicketController::class, 'comment'])->name('reply');
            Route::post('/{id}/assign', [App\Http\Controllers\Admin\TicketController::class, 'assign'])->name('assign');
            Route::post('/{id}/priority', [App\Http\Controllers\Admin\TicketController::class, 'updatePriority'])->name('update-priority');
            Route::post('/{id}/status', [App\Http\Controllers\Admin\TicketController::class, 'updateStatus'])->name('update-status');
            Route::post('/{id}/cancel', [App\Http\Controllers\Admin\TicketController::class, 'cancel'])->name('cancel');
            Route::post('/{id}/escalate', [App\Http\Controllers\Admin\TicketController::class, 'escalate'])->name('escalate');
            Route::get('/attachment/{attachment}/download', [App\Http\Controllers\Admin\TicketController::class, 'downloadAttachment'])->name('download');
            Route::delete('/attachment/{attachment}', [App\Http\Controllers\Admin\TicketController::class, 'deleteAttachment'])->name('delete-attachment');
            Route::post('/bulk/assign', [App\Http\Controllers\Admin\TicketController::class, 'bulkAssign'])->name('bulk.assign');
            Route::post('/bulk/status', [App\Http\Controllers\Admin\TicketController::class, 'bulkUpdateStatus'])->name('bulk.status');
            Route::post('/bulk/delete', [App\Http\Controllers\Admin\TicketController::class, 'bulkDelete'])->name('bulk.delete');
            Route::get('/export/csv', [App\Http\Controllers\Admin\TicketController::class, 'exportCSV'])->name('export.csv');
            Route::get('/export/pdf', [App\Http\Controllers\Admin\TicketController::class, 'exportPDF'])->name('export.pdf');
            // Real-time and message management routes
            Route::delete('/comment/{comment}', [App\Http\Controllers\Admin\TicketController::class, 'deleteComment'])->name('comment.delete');
            Route::post('/{id}/refresh-comments', [App\Http\Controllers\Admin\TicketController::class, 'refreshComments'])->name('refresh-comments');
            Route::post('/comment/{comment}/mark-read', [App\Http\Controllers\Admin\TicketController::class, 'markNotificationRead'])->name('comment.mark-read');
            Route::get('/unread-count', [App\Http\Controllers\Admin\TicketController::class, 'getUnreadCount'])->name('unread-count');
        });
        
        Route::get('/reports', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    });
});