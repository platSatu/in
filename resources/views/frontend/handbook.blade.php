<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handbook Universitas - InaStudy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f6f7fb;
            margin: 0;
        }

        .hero {
            background: linear-gradient(135deg, #1b2440 0%, #2d3a63 100%);
            color: #fff;
            padding: 60px 20px 90px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-radius: 0 0 32px 32px;
            min-height: 220px;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }

        .hero::after {
            content: "";
            position: absolute;
            bottom: -80px;
            left: -40px;
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .hero h1 {
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 10px;
            position: relative;
        }

        .hero p {
            font-size: 1rem;
            opacity: 0.85;
            max-width: 560px;
            margin: 0 auto;
            position: relative;
        }

        .badge-pill {
            display: inline-block;
            background: rgba(255, 255, 255, 0.14);
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.78rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 16px;
            font-weight: 600;
            position: relative;
        }

        .content-wrap {
            max-width: 900px;
            margin: -36px auto 60px;
            padding: 0 20px;
            position: relative;
        }

        .uni-card {
            background: #fff;
            border-radius: 16px;
            padding: 22px 26px;
            margin-bottom: 18px;
            box-shadow: 0 8px 24px rgba(20, 20, 43, 0.07);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #eef0f6;
        }

        .uni-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(20, 20, 43, 0.1);
        }

        .uni-icon {
            width: 50px;
            height: 50px;
            min-width: 50px;
            border-radius: 14px;
            background: #eef1fb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            overflow: hidden;
        }

        .uni-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .uni-info {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .uni-name {
            font-weight: 600;
            font-size: 1.02rem;
            color: #1c1c34;
            margin: 0;
        }

        .uni-sub {
            font-size: 0.8rem;
            color: #9296a8;
            margin: 0;
        }

        .btn-download {
            background: #2d3a63;
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.88rem;
            white-space: nowrap;
            transition: background 0.2s ease;
        }

        .btn-download:hover {
            background: #232d4d;
            color: #fff;
        }

        .empty-state {
            background: #fff;
            border-radius: 16px;
            padding: 60px 20px;
            text-align: center;
            color: #9296a8;
            box-shadow: 0 8px 24px rgba(20, 20, 43, 0.06);
            border: 1px solid #eef0f6;
        }

        .footer-note {
            text-align: center;
            color: #a3a7bd;
            font-size: 0.85rem;
            margin-top: 30px;
        }

        @media (max-width: 576px) {
            .uni-card {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .uni-info {
                flex-direction: column;
            }

            .btn-download {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="hero">
        <span class="badge-pill">Free Download</span>
        <h1>Handbook Universitas</h1>
        <p>Explore a collection of official university guides — download them for free and keep them with you as a trusted reference anytime.</p>
    </div>

    <div class="content-wrap">

        @if($universities->isEmpty())
            <div class="empty-state">
                <div style="font-size: 2rem; margin-bottom: 10px;">📭</div>
                There are no university handbooks available yet. Please check back later.
            </div>
        @else
            @foreach($universities as $university)
                <div class="uni-card">
                    <div class="uni-info">
                        <div class="uni-icon">
                            @if($university->logo)
                                <img src="{{ asset($university->logo) }}" alt="{{ $university->name }}">
                            @else
                                🎓
                            @endif
                        </div>
                        <div>
                            <p class="uni-name">{{ $university->name }}</p>
                            <p class="uni-sub">Official Handbook</p>
                        </div>
                    </div>

                    <a href="{{ route('frontend.handbook.download', $university->id) }}" class="btn btn-download">
                        ⬇ Download
                    </a>
                </div>
            @endforeach
        @endif

        <p class="footer-note">© {{ date('Y') }} InaStudy</p>

    </div>

</body>

</html>