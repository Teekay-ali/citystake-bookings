<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance index migration — CityStake Bookings
 *
 * Covers gaps left by the original 2026_02_20_045236 index migration.
 * All indexes use InnoDB online DDL — safe to run on live production
 * without locking tables (MySQL 5.6+ / 8.x).
 *
 * Grouped by table with a comment explaining the query each index supports.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // bookings
        // ──────────────────────────────────────────────
        Schema::table('bookings', function (Blueprint $table) {

            // Dashboard revenue: payment_status='paid' + year/month on created_at
            $table->index(['payment_status', 'created_at'], 'bookings_payment_status_created_at_idx');

            // Availability board + calendar: building-scoped confirmed/pending range queries
            $table->index(['building_id', 'status', 'check_in', 'check_out'], 'bookings_building_status_dates_idx');

            // ExpireUnpaidBookings command:
            //   WHERE status='pending' AND payment_status='pending' AND created_at < ?
            $table->index(['status', 'payment_status', 'created_at'], 'bookings_status_payment_created_idx');

            // Soft-delete scoping — every query auto-appends AND deleted_at IS NULL
            $table->index('deleted_at', 'bookings_deleted_at_idx');
        });

        // ──────────────────────────────────────────────
        // financial_transactions  ← highest-priority table
        // Every financial page (ledger, P&L, trend) queries this heavily
        // ──────────────────────────────────────────────
        Schema::table('financial_transactions', function (Blueprint $table) {

            // Core ledger + summary: building scoped by date range
            $table->index(['building_id', 'transaction_date'], 'ft_building_date_idx');

            // Type filter (income / expense) on ledger page
            $table->index(['building_id', 'type', 'transaction_date'], 'ft_building_type_date_idx');

            // Category filter on ledger page
            $table->index(['building_id', 'category', 'transaction_date'], 'ft_building_category_date_idx');

            // Polymorphic back-reference: "all transactions for Booking #42"
            $table->index(['reference_type', 'reference_id'], 'ft_reference_type_id_idx');
        });

        // ──────────────────────────────────────────────
        // notifications
        // Bell unread count: notifiable_id + notifiable_type + read_at IS NULL
        // morphs() already creates (notifiable_type, notifiable_id) but without read_at
        // ──────────────────────────────────────────────
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'notifications_notifiable_read_idx');
        });

        // ──────────────────────────────────────────────
        // audit_logs
        // ──────────────────────────────────────────────
        Schema::table('audit_logs', function (Blueprint $table) {

            // Action filter on audit log index page (exact match + prefix LIKE)
            $table->index('action', 'audit_logs_action_idx');

            // Date filter on audit log index page
            $table->index('created_at', 'audit_logs_created_at_idx');
        });

        // ──────────────────────────────────────────────
        // tasks
        // ──────────────────────────────────────────────
        Schema::table('tasks', function (Blueprint $table) {

            // Scoped task list with status + overdue detection (due_date < today)
            $table->index(['building_id', 'status', 'due_date'], 'tasks_building_status_due_idx');

            // Filter by assigned staff member
            $table->index('assigned_to', 'tasks_assigned_to_idx');
        });

        // ──────────────────────────────────────────────
        // maintenance_reports
        // ──────────────────────────────────────────────
        Schema::table('maintenance_reports', function (Blueprint $table) {

            // Index + status filter: building-scoped status lists
            $table->index(['building_id', 'status'], 'maintenance_building_status_idx');

            // Soft-delete scoping
            $table->index('deleted_at', 'maintenance_deleted_at_idx');
        });

        // ──────────────────────────────────────────────
        // complaints
        // ──────────────────────────────────────────────
        Schema::table('complaints', function (Blueprint $table) {

            // Index + status filter
            $table->index(['building_id', 'status'], 'complaints_building_status_idx');

            // Soft-delete scoping
            $table->index('deleted_at', 'complaints_deleted_at_idx');
        });

        // ──────────────────────────────────────────────
        // procurement_requests
        // ──────────────────────────────────────────────
        Schema::table('procurement_requests', function (Blueprint $table) {

            // Approval queue: building-scoped by workflow status
            $table->index(['building_id', 'status'], 'procurement_building_status_idx');
        });

        // ──────────────────────────────────────────────
        // stock_logs
        // ──────────────────────────────────────────────
        Schema::table('stock_logs', function (Blueprint $table) {

            // Usage history per stock item (paginated, ordered by created_at)
            $table->index(['stock_item_id', 'created_at'], 'stock_logs_item_created_idx');
        });

        // ──────────────────────────────────────────────
        // booking_messages
        // ──────────────────────────────────────────────
        Schema::table('booking_messages', function (Blueprint $table) {

            // Per-booking message thread + unread filter
            $table->index(['booking_id', 'read_at'], 'booking_messages_booking_read_idx');

            // Sidebar unread badge: guest messages with read_at IS NULL across all bookings
            $table->index(['sender_type', 'read_at'], 'booking_messages_sender_read_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_payment_status_created_at_idx');
            $table->dropIndex('bookings_building_status_dates_idx');
            $table->dropIndex('bookings_status_payment_created_idx');
            $table->dropIndex('bookings_deleted_at_idx');
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropIndex('ft_building_date_idx');
            $table->dropIndex('ft_building_type_date_idx');
            $table->dropIndex('ft_building_category_date_idx');
            $table->dropIndex('ft_reference_type_id_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_action_idx');
            $table->dropIndex('audit_logs_created_at_idx');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_building_status_due_idx');
            $table->dropIndex('tasks_assigned_to_idx');
        });

        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->dropIndex('maintenance_building_status_idx');
            $table->dropIndex('maintenance_deleted_at_idx');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex('complaints_building_status_idx');
            $table->dropIndex('complaints_deleted_at_idx');
        });

        Schema::table('procurement_requests', function (Blueprint $table) {
            $table->dropIndex('procurement_building_status_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropIndex('stock_logs_item_created_idx');
        });

        Schema::table('booking_messages', function (Blueprint $table) {
            $table->dropIndex('booking_messages_booking_read_idx');
            $table->dropIndex('booking_messages_sender_read_idx');
        });
    }
};
