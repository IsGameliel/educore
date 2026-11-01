<?php

use App\Mail\DemoRequestMail;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

function demoRequestData(): array
{
    return [
        'name' => 'Demo Contact',
        'email' => 'demo@example.org',
        'institution' => 'Example Institution',
        'role' => 'Registrar',
        'student_count' => '500-1999',
        'topics' => ['registration', 'broadsheets'],
        'message' => 'We would like to explore registration and reports.',
        'consent' => '1',
    ];
}

it('shows the Euvion demo form with a real submission route and contact address', function () {
    $this->get(route('demo.show'))->assertOk()
        ->assertSee('See your campus workflows come together.')
        ->assertSee('contact@euvion.com.ng')
        ->assertSee(route('demo.store'))
        ->assertSee('name="_token"', false)
        ->assertDontSee('98%')->assertDontSee('AI-Driven Insights');
});

it('emails validated demo requests to Euvion with the requester as reply-to', function () {
    Mail::fake();
    $this->post(route('demo.store'), demoRequestData())->assertRedirect(route('demo.show'))->assertSessionHas('demo_sent', true);
    Mail::assertSent(DemoRequestMail::class, function ($mail) {
        return $mail->hasTo('contact@euvion.com.ng')
            && $mail->envelope()->replyTo[0]->address === 'demo@example.org'
            && $mail->details['institution'] === 'Example Institution'
            && $mail->details['topics'] === ['Student records and course registration', 'Broadsheets and academic reports'];
    });
    Mail::assertSentCount(1);
    $this->get(route('demo.show'))->assertOk()->assertSee('Your demo request has been sent.')->assertSee('A meeting time has not yet been confirmed.');
});

it('rejects incomplete or invalid demo requests without emailing', function () {
    Mail::fake();
    $this->from(route('demo.show'))->post(route('demo.store'), [
        'email' => 'invalid-email', 'role' => 'Unknown', 'topics' => ['unsupported'], 'student_count' => 'invalid',
    ])->assertRedirect(route('demo.show'))->assertSessionHasErrors(['name', 'email', 'institution', 'role', 'topics.0', 'student_count', 'consent']);
    Mail::assertNothingSent();
});

it('retains demo details and reports transport failures without a false success', function () {
    Mail::shouldReceive('to')->once()->with('contact@euvion.com.ng')->andReturnSelf();
    Mail::shouldReceive('send')->once()->andThrow(new TransportException('Test delivery failure'));
    $this->post(route('demo.store'), demoRequestData())->assertRedirect(route('demo.show'))
        ->assertSessionHasErrors('delivery')->assertSessionHasInput('institution', 'Example Institution')
        ->assertSessionMissing('demo_sent');
});

it('rate limits public demo request submissions', function () {
    Mail::fake();
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->post(route('demo.store'), demoRequestData())->assertRedirect();
    }
    $this->post(route('demo.store'), demoRequestData())->assertStatus(429);
    Mail::assertSentCount(3);
});

it('escapes visitor content in demo emails', function () {
    $data = demoRequestData();
    $data['topics'] = ['Course registration'];
    $data['message'] = '<script>alert("test")</script>';
    $html = (new DemoRequestMail($data))->render();
    expect($html)->toContain('&lt;script&gt;')->not->toContain('<script>');
});
