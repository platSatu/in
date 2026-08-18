<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - {{ $invitation->name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
        }

        .invitation-card {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .12);
        }

        /* Header gradien */
        .invitation-header {
            background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
            color: white;
            padding: 32px 24px 24px;
            text-align: center;
        }

        .invitation-header .badge-status {
            display: inline-block;
            background: rgba(255, 255, 255, .2);
            border: 1px solid rgba(255, 255, 255, .4);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 12px;
            letter-spacing: .5px;
            margin-bottom: 12px;
        }

        .invitation-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .invitation-header small {
            opacity: .8;
            font-size: 13px;
        }

        /* Body info */
        .invitation-body {
            padding: 24px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-icon {
            font-size: 18px;
            min-width: 28px;
            text-align: center;
            margin-top: 2px;
        }

        .info-label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a1a;
        }

        /* QR section */
        .qr-section {
            background: #f8f9ff;
            border: 2px dashed #c5cae9;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin: 20px 0;
        }

        .qr-section img {
            width: 200px;
            height: 200px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
        }

        .qr-code-text {
            display: inline-block;
            background: #ac0606;
            color: white;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 1.5px;
            padding: 6px 16px;
            border-radius: 20px;
            margin-top: 12px;
        }

        .qr-note {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }

        /* Footer */
        .invitation-footer {
            background: #f8f9ff;
            padding: 16px 24px;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .btn-download {
            background: linear-gradient(135deg, #ac0606, #8b0000);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: opacity .2s;
        }

        .btn-download:hover {
            opacity: .9;
            color: white;
        }

        .wa-sent-badge {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 16px;
        }

        @media print {
            body {
                background: white;
            }

            .btn-download,
            .wa-sent-badge {
                display: none;
            }

            .invitation-card {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

    <div class="container py-5">

        <div class="invitation-card">

            {{-- Header --}}
           <div class="invitation-header text-white position-relative overflow-hidden"
                    style="
                        background: url('{{ asset('frontend/img/bg1.jpg') }}') center center / cover no-repeat;
                    ">

                    <div class="position-absolute top-0 start-0 w-100 h-100"></div>

                    <div class="position-relative">
                        <img src="{{ asset('frontend/img/Logo.png') }}" alt="Logo" class="mb-3" style="width: 150px;">
                        <img src="{{ asset('frontend/img/text.png') }}" alt="Logo" class="mb-3" style="width: 300px;">
                        <div class="badge-status fw-bold" style="font-family: 'Cormorant Garamond', serif; font-size: 1.25rem; color:#ac0606;">INVITATION CREATED SUCCESSFULLY</div>
                        <h2 class="" style="font-size: 1.25rem; font-weight: bold; background: #ac0606; color: white; padding: 10px; border-radius:10px;">{{ $invitation->name }}</h2>
                        <small class="fw-bold" style="font-family: 'Cormorant Garamond', serif; font-size: 1rem; color:#ac0606;">Registered on {{ $invitation->created_at->format('d M Y, H:i') }}</small>
                    </div>

                </div>

            <div class="invitation-body">

                {{-- WA sudah dikirim --}}
                <div class="wa-sent-badge">
                    The QR Code has been successfully sent via WhatsApp {{ $invitation->handphone }}
                </div>

                {{-- Info peserta --}}
                @if ($invitation->university)
                    <div class="info-row">
                        <div class="info-icon">🏫</div>
                        <div>
                            <div class="info-label">University / Institution</div>
                            <div class="info-value">{{ $invitation->university }}</div>
                        </div>
                    </div>
                @endif

                @if ($invitation->program)
                    <div class="info-row">
                        <div class="info-icon">📚</div>
                        <div>
                            <div class="info-label">Program / Major</div>
                            <div class="info-value">{{ $invitation->program }}</div>
                        </div>
                    </div>
                @endif

                @if ($invitation->number_of_attendes)
                    <div class="info-row">
                        <div class="info-icon">👥</div>
                        <div>
                            <div class="info-label">Number of Attendees</div>
                            <div class="info-value">{{ $invitation->number_of_attendes }} people</div>
                        </div>
                    </div>
                @endif

                <div class="info-row">
                        <div class="info-icon">📌</div>
                        <div>
                            <div class="info-label">Date Time Location</div>
                            <div class="info-value">1 August 2026 | 3 PM - Finished | <a href="https://share.google/5Th9hFyNB1DgHVogh" target="_blank">
                               Flix Cinema, 2nd Floor Mall of Indonesia 
                            </a></div>
                        </div>
                </div>
                {{-- QR Code --}}
                <div class="qr-section">
                    <div class="info-label mb-3" style="font-size:13px;">🎫 YOUR QR CODE</div>

                    @if ($invitation->directory_qrcode && file_exists(public_path($invitation->directory_qrcode)))
                        <div style="display: inline-block; background: white; padding: 20px; border-radius: 10px;">
                            <img src="{{ url($invitation->directory_qrcode) }}" alt="QR Code {{ $invitation->name }}"
                                style="display: block;">
                        </div>
                        <br>
                    @else
                        <div class="text-muted py-4">QR Code is not available</div>
                    @endif

                    <div class="qr-code-text">{{ $invitation->qrcode }}</div>
                    <div class="qr-note mt-2">
                        📌 Show this QR code when re-registering at the event location
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="invitation-footer">
                <a href="{{ url($invitation->directory_qrcode) }}" download="qrcode-{{ $invitation->qrcode }}.png"
                    class="btn-download">
                    Download QR Code
                </a>
                <div class="mt-3">
                    <a href="{{ route('invitation.create') }}" class="text-muted" style="font-size:13px;">
                        ← Register Another Guest
                    </a>
                </div>
                <div class="card-footer text-center text-muted py-3">
                    <small style="font-family: 'Cormorant Garamond', serif;";>&copy; {{ date('Y') }} Inagroup. All rights reserved.</small>
                </div>
            </div>

        </div>

    </div>

</body>

</html>
