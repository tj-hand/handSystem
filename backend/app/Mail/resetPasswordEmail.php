<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class resetPasswordEmail extends Mailable
{
	use Queueable, SerializesModels;

	private $resetLink;

	public function __construct($resetLink)
	{
		$this->resetLink = $resetLink;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build()
	{
		// Define the view, subject, and content for the email
		return $this->view('emails.resetPassword')
			->subject(__('email_reset_password.subject'))
			->with(['resetLink' => $this->resetLink]);
	}
}
