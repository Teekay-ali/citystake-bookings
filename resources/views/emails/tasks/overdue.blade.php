@extends('emails.layout')

@section('content')
    <h1 class="email-title">{{ $title }}</h1>

    <p class="email-text">{{ $body }}</p>

    <div class="booking-card">
        <div class="booking-detail">
            <span class="booking-label">Task</span>
            <span class="booking-value">{{ $task->title }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Priority</span>
            <span class="booking-value">{{ ucfirst($task->priority) }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Due Date</span>
            <span class="booking-value" style="color: #dc2626;">{{ $task->due_date?->format('M d, Y') }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Building</span>
            <span class="booking-value">{{ $task->building?->name }}</span>
        </div>
        @if($task->description)
            <div class="booking-detail" style="display:block; padding: 12px 0;">
                <span class="booking-label">Description</span>
                <p style="margin-top:8px; font-size:14px; color:#374151; line-height:1.6;">{{ $task->description }}</p>
            </div>
        @endif
    </div>

    <center>
        <a href="{{ route('manage.tasks.show', $task->id) }}" class="button">
            View Task
        </a>
    </center>
@endsection
