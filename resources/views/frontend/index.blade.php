<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Kesehatan Mental</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:#f5f7fb;
        }

        .wizard-card{
            max-width:600px;
            margin:auto;
            margin-top:70px;
            border:none;
            border-radius:15px;
            box-shadow:0 10px 25px rgba(0,0,0,.08);
        }

        .step{
            display:none;
        }

        .step.active{
            display:block;
        }

        .progress{
            height:8px;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="card wizard-card">

        <div class="card-body p-5">

            <h3 class="text-center mb-2">
                Quiz
            </h3>

            <p class="text-center text-muted mb-4">
                Jawablah pertanyaan dengan jujur.
            </p>

            <div class="progress mb-4">
                <div class="progress-bar"
                     id="progressBar"
                     style="width:50%">
                </div>
            </div>

            <form>

                {{-- STEP 1 --}}
                <div class="step active" id="step1">

                    <h5 class="mb-4">
                        Langkah 1 dari 2
                    </h5>

                    <div class="mb-3">
                        <label class="form-label">
                            Nama Lengkap
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            placeholder="Masukkan nama">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            Nomor WhatsApp
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            placeholder="62812xxxxxxxx">
                    </div>

                    <div class="text-end">
                        <button
                            type="button"
                            class="btn btn-primary"
                            onclick="nextStep()">

                            Lanjut →
                        </button>
                    </div>

                </div>

                {{-- STEP 2 --}}
                <div class="step" id="step2">

                    <h5 class="mb-4">
                        Langkah 2 dari 2
                    </h5>

                    <div class="alert alert-info">

                        <strong>Petunjuk:</strong>

                        <ul class="mb-0 mt-2">
                            <li>Jawab semua pertanyaan.</li>
                            <li>Tidak ada jawaban benar atau salah.</li>
                            <li>Hasil akan dikirim melalui WhatsApp.</li>
                        </ul>

                    </div>

                    <div class="d-flex justify-content-between">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            onclick="prevStep()">

                            ← Kembali
                        </button>

                        <button
                            type="submit"
                            class="btn btn-success">

                            Mulai Quiz
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

function nextStep(){

    document.getElementById('step1').classList.remove('active');
    document.getElementById('step2').classList.add('active');

    document.getElementById('progressBar').style.width='100%';

}

function prevStep(){

    document.getElementById('step2').classList.remove('active');
    document.getElementById('step1').classList.add('active');

    document.getElementById('progressBar').style.width='50%';

}

</script>

</body>
</html>