<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BackupController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\ChatMemberController;
use App\Http\Controllers\API\ChatMessageController;
use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\API\RoomImageController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/signup', [AuthController::class, 'signup']); // Register a new user and return token
Route::post('/signin', [AuthController::class, 'signin']); // Authenticate user and return token
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware('signed')->name('verify.email'); // Verify email address
Route::post('/email/verify/resend', [AuthController::class, 'resendVerificationMail'])->middleware('throttle:60,1'); // Resend verification email
Route::post('/password/forgot', [AuthController::class, 'sendResetPasswordMail']); // Send password reset link
Route::post('/password/reset', [AuthController::class, 'setNewPassword'])->name('reset.password'); // Reset password using token


// Google OAuth routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// Group protected routes under auth:sanctum middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/signout', [AuthController::class, 'signout']);
    Route::PATCH('/token/refresh', [AuthController::class, 'refreshToken']);
    Route::get('/verify/account', [AuthController::class, 'verifyAccount']);
    Route::put('/password/change', [AuthController::class, 'changePassword']);
    Route::put('/password/create', [AuthController::class, 'createPassword']);
    // ... existing routes
    Route::put('/update/photo', [AuthController::class, 'updateUserPhoto']);


    // Backup routes
    // Admin-only routes
    Route::middleware('admin')->group(function () {
        Route::prefix('backups')->group(function () {
            Route::get('/', [BackupController::class, 'getBackups']);
            Route::post('/create', [BackupController::class, 'createBackup']);
            Route::get('/download/{filename}', [BackupController::class, 'downloadBackup']);
            Route::delete('/delete/{filename}', [BackupController::class, 'deleteBackup']);
        });
        Route::prefix('manage')->group(function () {
            Route::prefix('users')->group(function () {
                Route::get('/', [UserController::class, 'getDetailUsers']);
                Route::get('/read/{id}', [UserController::class, 'readDetailUser']);
                Route::post('/create', [UserController::class, 'createUser']);
                Route::put('/update/{id}', [UserController::class, 'updateUser']);
                Route::delete('/delete/{id}', [UserController::class, 'deleteUser']);
            });
        });
        // Departments
        Route::prefix('departments')->group(function () {
            Route::get('/', [DepartmentController::class, 'index']);
            Route::post('/create', [DepartmentController::class, 'store']);
            Route::get('/read/{department}', [DepartmentController::class, 'show']);
            Route::put('/update/{department}', [DepartmentController::class, 'update']);
            Route::delete('/delete/{department}', [DepartmentController::class, 'destroy']);
        });
        // Rooms
        Route::prefix('rooms')->group(function () {

            Route::get('/', [RoomController::class, 'index']);
            Route::post('/create', [RoomController::class, 'store']);

            Route::get('/read/{room}', [RoomController::class, 'show']);
            Route::post('/update/{room}', [RoomController::class, 'update']);

            Route::delete('/delete/{room}', [RoomController::class, 'destroy']);

            Route::delete('/delete-image/{room}/{image}', [RoomController::class, 'deleteImage']);
        });
        // Room Images
        Route::prefix('room-images')->group(function () {

            Route::post('/upload/{roomId}', [RoomImageController::class, 'upload']);

            Route::delete('/delete/{imageId}', [RoomImageController::class, 'delete']);
        });
        // Booking MS

    });
    // ... existing routes
    // User API Routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'getUsers']);
    });
    // Chat API Routes
    Route::prefix('chats')->group(function () {
        // Chat management
        Route::get('/', [ChatController::class, 'getChats']);
        Route::post('/create', [ChatController::class, 'createChat']);
        Route::get('/read/{chatId}', [ChatController::class, 'readChat']);
        Route::put('/update/{chatId}', [ChatController::class, 'updateGroupChat']);
        Route::delete('/delete/{chatId}', [ChatController::class, 'deleteGroupChat']);
        Route::post('/leave/{chatId}', [ChatController::class, 'leaveGroupChat']);
        Route::get('/read/{chatId}/{folder}/{filename}', [ChatController::class, 'readChatFile']);


        // Chat messages
        Route::get('/{chatId}/messages', [ChatMessageController::class, 'getMessages']);
        Route::post('/{chatId}/messages/create', [ChatMessageController::class, 'createMessage']);
        Route::put('/{chatId}/messages/update/{messageId}', [ChatMessageController::class, 'updateMessage']);
        Route::delete('/{chatId}/messages/delete/{messageId}', [ChatMessageController::class, 'deleteMessage']);
        Route::post('/{chatId}/messages/seen/{messageId}', [ChatMessageController::class, 'markMessageAsSeen']);
        Route::post('/{chatId}/messages/seen-all', [ChatMessageController::class, 'markAllMessagesAsSeen']);

        // Chat members
        Route::get('/{chatId}/members', [ChatMemberController::class, 'getMembers']);
        Route::post('/{chatId}/members/add', [ChatMemberController::class, 'addMember']);
        Route::put('/{chatId}/members/update/{memberId}', [ChatMemberController::class, 'updateMember']);
        Route::delete('/{chatId}/members/remove/{memberId}', [ChatMemberController::class, 'removeMember']);
    });

    // Bookings
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/create', [BookingController::class, 'store']);
        Route::get('/read/{booking}', [BookingController::class, 'show']);
        Route::put('/update/{booking}', [BookingController::class, 'update']);

        Route::get('/availability', [BookingController::class, 'availability']);
        Route::get('/calendar', [BookingController::class, 'calendar']);

        Route::put('/request-cancel/{booking}', [BookingController::class, 'requestCancel']);

        Route::put('/approve/{booking}', [BookingController::class, 'approve']);
        Route::put('/reject/{booking}', [BookingController::class, 'reject']);
        Route::put('/confirm-cancel/{booking}', [BookingController::class, 'confirmCancel']);
        Route::put('/admin-cancel/{booking}', [BookingController::class, 'adminCancel']);
        Route::delete('/delete/{booking}', [BookingController::class, 'destroy']);
    });

    Route::prefix('rooms')->group(function () {
        Route::get('/', [RoomController::class, 'index']);
        Route::get('/read/{room}', [RoomController::class, 'show']);
    });

    Route::get('/dashboard', [BookingController::class, 'dashboard']);
});
