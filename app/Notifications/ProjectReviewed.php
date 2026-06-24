<?php

namespace App\Notifications;

use App\Models\ProjectSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProjectReviewed extends Notification
{
    use Queueable;

    public $submission;

    public function __construct(ProjectSubmission $submission)
    {
        $this->submission = $submission;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'title' => $this->submission->title,
            'status' => $this->submission->status,
            'message' => 'Your project "' . $this->submission->title . '" was ' . $this->submission->status,
        ];
    }
}
