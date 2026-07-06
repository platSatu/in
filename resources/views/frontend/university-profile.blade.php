<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $university->name }} - InaStudy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .profile-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }

        .university-logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
            color: #0d6efd;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: #e7f1ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .btn-whatsapp {
            background: #25D366;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-whatsapp:hover {
            background: #20BD5A;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }

        .tag {
            display: inline-block;
            background: #e7f1ff;
            color: #0d6efd;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="university-logo">
                        {{ substr($university->name, 0, 1) }}
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-2">{{ $university->name }}</h1>
                    <p class="mb-0 opacity-75">
                        📍 {{ $university->city }}, {{ $university->country }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="info-card">
                    <h4 class="mb-4">Deskripsi</h4>
                    <p>{{ $university->description ?? 'Deskripsi tidak tersedia.' }}</p>
                </div>

                <div class="info-card">
                    <h4 class="mb-4">Bidang Studi</h4>
                    <div>
                        @if($profile->field)
                            @foreach(explode(',', $profile->field) as $field)
                                <span class="tag">{{ trim($field) }}</span>
                            @endforeach
                        @else
                            <p class="text-muted">Informasi tidak tersedia</p>
                        @endif
                    </div>
                </div>

                <div class="info-card">
                    <h4 class="mb-4">Bahasa Pengantar</h4>
                    <p>{{ $profile->language ?? 'Informasi tidak tersedia' }}</p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="info-card">
                    <h5 class="mb-4">Informasi Biaya</h5>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Budget</small>
                        <strong class="text-primary">
                            @if($profile->min_budget && $profile->max_budget)
                                Rp {{ number_format($profile->min_budget, 0, ',', '.') }} - Rp {{ number_format($profile->max_budget, 0, ',', '.') }}
                            @elseif($profile->min_budget)
                                Rp {{ number_format($profile->min_budget, 0, ',', '.') }}+
                            @elseif($profile->max_budget)
                                Up to Rp {{ number_format($profile->max_budget, 0, ',', '.') }}
                            @else
                                Hubungi kami
                            @endif
                        </strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Beasiswa</small>
                        <strong>{{ $profile->scholarship_available ? 'Tersedia' : 'Tidak Tersedia' }}</strong>
                    </div>
                </div>

                <div class="info-card text-center">
                    <h5 class="mb-4">Tertarik?</h5>
                    <p class="text-muted mb-3">Konsultasi gratis dengan tim kami</p>
                    <a href="https://wa.me/6281234567890?text=Halo%20InaStudy,%20saya%20ingin%20belajar%20di%20{{ urlencode($university->name) }}" 
                       class="btn-whatsapp" 
                       target="_blank">
                        💬 Hubungi via WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 mb-5">
            <a href="{{ route('frontend.form.wizard') }}" class="btn btn-outline-secondary">
                ← Kembali ke Form Quiz
            </a>
        </div>
    </div>

</body>

</html>
