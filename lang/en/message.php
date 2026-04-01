<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Message Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various messages
    | that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    // General Messages
    'ms_success' => 'You do not have permission to access this resource.',
    'created_success' => 'Created successfully',
    'updated_success' => 'Updated successfully',
    'deleted_success' => 'Deleted successfully',
    'data_not_found' => 'Data Not Found',
    'can_not_delete' => 'Can not be delete',
    'data_in_use' => 'Can not be delete data because it is in use',

    // User Messages
    'user_created' => 'User created.',
    'user_updated' => 'User updated.',
    'user_deleted' => 'User deleted.',
    'failed_create_user' => 'Failed to create user',
    'failed_update_user' => 'Failed to update user',
    'failed_delete_user' => 'Failed to delete user',
    'only_super_admin_change_levels' => 'Only super admin can change user levels.',

    // Room Messages
    'image_deleted_success' => 'Image deleted successfully',

    // Notification Messages
    'notification_marked_read' => 'Notification marked as read.',
    'all_notifications_marked_read' => 'All notifications marked as read.',
    'notification_deleted' => 'Notification deleted.',

    // Booking Messages
    'unauthorized' => 'Unauthorized',
    'room_already_booked' => 'Room is already booked for this time',
    'past_bookings_cannot_update' => 'Past bookings cannot be updated',
    'only_pending_bookings_update' => 'Only pending bookings can be updated',
    'past_bookings_cannot_cancel' => 'Past bookings cannot request cancellation',
    'only_approved_bookings_cancel' => 'Only approved bookings can request cancellation',
    'cancel_request_submitted' => 'Cancel request submitted successfully',
    'past_bookings_cannot_approve' => 'Past bookings cannot be approved',
    'only_pending_bookings_approve' => 'Only pending bookings can be approved',
    'booking_approved_success' => 'Booking approved successfully',
    'past_bookings_cannot_reject' => 'Past bookings cannot be rejected',
    'only_pending_bookings_reject' => 'Only pending bookings can be rejected',
    'booking_rejected_success' => 'Booking rejected successfully',
    'past_bookings_cannot_confirm_cancel' => 'Past bookings cannot confirm cancellation',

    // Department Messages
    'department_deleted' => 'Department deleted',

];
