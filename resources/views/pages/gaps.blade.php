@extends('layouts.app')
@section('title', 'Not recorded yet')
@section('content')
<div class="view-head"><h1>Not recorded yet</h1>
  <p>What this dashboard can't show because the apps never save it. Found by reading the code on Nov 5. Each fix is a change inside that app.</p></div>

@php
  $gaps = [
    ['crit', 'my-genone', 'RFID endpoints have no authentication', 'Anyone who can reach the site and knows a card UID can clock that person in or out. device_id is optional and the cameras send none.', 'Per-device key in a header, checked by middleware, with a short grace period until the boards are re-flashed. Make device_id required.'],
    ['crit', 'my-genone', 'Emails are sent inside the request and never logged', '21 email types go to Office 365 synchronously. If one is refused, the approval is already saved but the page errors. Announcements send one email per person in one page load.', 'Queue the notifications (QUEUE_CONNECTION=database already exists) and save NotificationSent / NotificationFailed into an email log.'],
    ['crit', 'my-genone', 'RFID requests aren\'t logged', 'Unregistered cards, "too soon" taps and which floor sent what only exist in the answer to the device.', 'An api_request_log written by middleware on routes/api.php: time, device, UID (masked), result, status, ms.'],
    ['warn', 'my-genone', 'The reason a face wasn\'t checked is thrown away', 'match_result (no_reference / reference_unreachable / error) is returned to the camera, but only face_match = null is saved.', 'Add a match_result column to time_entries and save it with face_match.'],
    ['warn', 'my-genone', 'Scheduled jobs leave no trace', 'Nothing records whether clock-in reminders, mark-absentees or the office-visit reminder actually ran.', 'Listen to ScheduledTaskFinished / ScheduledTaskFailed and save command, time, duration and exit code.'],
    ['warn', 'my-genone', 'Calls to 201 and face-service aren\'t measured', 'They only write to the Laravel log. The compare call uses raw Guzzle, which skips Laravel\'s Http events.', 'Switch compare to Http:: and save ResponseReceived / ConnectionFailed timings.'],
    ['warn', 'PayMaya', 'Rejected uploads aren\'t stored', 'The employee sees an error; the reason (layout, badge, username, name) is lost.', 'A verification_attempts table with the failed check and OCR fields.'],
    ['warn', '201 System', 'Rejected maintenance reports are dropped', 'Reports with unfinished steps are refused and not saved, so failing PCs don\'t show up.', 'Save them with status "rejected" and the missing steps.'],
    ['idle', 'All apps', 'Exceptions only go to Railway logs', 'Logs roll over and can\'t be grouped or counted.', 'A report() hook in bootstrap/app.php that saves class, message, file:line and a hash.'],
  ];
@endphp

<div class="panel"><div class="gaps">
  <div class="gaprow headrow"><span class="label">App</span><span class="label">What's missing</span><span class="label">What to add</span></div>
  @foreach($gaps as [$sev, $app, $title, $why, $fix])
    <div class="gaprow">
      <span><span class="pill {{ $sev }} sev">{{ ['crit' => 'Do first', 'warn' => 'Next', 'idle' => 'Later'][$sev] }}</span><br>{{ $app }}</span>
      <span><b style="font-weight:600">{{ $title }}.</b> {{ $why }}</span>
      <span class="fix">{{ $fix }}</span>
    </div>
  @endforeach
</div></div>
<p class="muted" style="font-size:13px">Once an app records one of these, its numbers get a section here: API requests and devices, outgoing email, job runs and errors.</p>
@endsection
