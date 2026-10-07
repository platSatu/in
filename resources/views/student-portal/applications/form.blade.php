<!--
    FASE 3 (Alur Pembayaran 2 Arah Apply Kampus, 10 September 2026) -- Step 1
    SETELAH Registration Fee lunas: Formulir biodata lengkap diisi LANGSUNG
    di web (menggantikan upload file Word/PDF manual untuk DocumentType
    'formulir', lihat DocumentTypeSeeder), field-nya mengikuti contoh form
    Word asli per-kampus ("Nanjing Tech Form") yang dikirim user.

    Bagian "Education Background" (fitur "add row") dikelola lewat JS
    sederhana di bawah -- baris baru di-clone dari <template>, index array
    diberi ulang tiap kali baris ditambah/dihapus supaya urutan
    education[0], education[1], dst selalu rapat (tidak bolong) waktu
    disubmit.

    Setelah Terms & Condition dicentang & disubmit, ApplicationFormController
    ::update() set terms_accepted_at lalu redirect ke Step 2 (Upload
    Documents) -- lihat guard blockIfFormNotSubmitted() di
    ApplicationDocumentController.
-->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir - {{ $application->application_no }} | INASTUDY</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #C8102E; --brand-dark: #a30d25; }
        * { box-sizing: border-box; }
        body {
            background: #f5f7fb;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2b2f38;
        }
        .topbar { background: #fff; border-bottom: 1px solid #eef1f8; padding: 16px 0; }
        .topbar a { color: #6b7186; text-decoration: none; font-weight: 600; font-size: 14px; }
        .topbar a:hover { color: var(--brand); }
        .page-wrap { max-width: 900px; margin: 0 auto; padding: 32px 16px 60px; }
        .card-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 20px rgba(20,30,60,.05);
            margin-bottom: 20px;
        }
        .card-box h1 { font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; }
        .card-box .subtitle { color: #6b7186; font-size: 14.5px; margin-bottom: 0; }
        .section-title {
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #8a90a2;
            margin: 8px 0 16px;
        }
        label.form-label { font-size: 13.5px; font-weight: 600; color: #4a5063; }
        .edu-row { border: 1px solid #eef1f8; border-radius: 12px; padding: 14px; margin-bottom: 12px; position: relative; }
        .edu-row .btn-remove-row {
            position: absolute; top: 10px; right: 10px; border: none; background: none;
            color: #c9cddb; font-size: 18px; line-height: 1; cursor: pointer;
        }
        .edu-row .btn-remove-row:hover { color: var(--brand); }
        .btn-brand {
            background: var(--brand);
            border: none;
            color: #fff;
            padding: 13px 20px;
            border-radius: 12px;
            font-weight: 700;
        }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }
        .btn-add-row {
            border: 2px dashed #e9ecef;
            background: #fff;
            color: #6b7186;
            font-weight: 700;
            font-size: 13.5px;
            border-radius: 10px;
            padding: 10px 16px;
            width: 100%;
        }
        .btn-add-row:hover { border-color: var(--brand); color: var(--brand); }
        .terms-box {
            border: 1px solid #eef1f8;
            border-radius: 12px;
            padding: 16px 18px;
            max-height: 420px;
            overflow-y: auto;
            font-size: 13px;
            color: #4a5063;
            background: #f8f9fc;
        }
        .terms-box ol { padding-left: 18px; margin-bottom: 0; }
        .terms-box li { margin-bottom: 14px; }
        .terms-box p { margin: 4px 0 0; }
        .terms-box p[lang="en"] { color: #6b7186; font-style: italic; }
        .terms-box p[lang="zh"] { color: #6b7186; }
        .terms-title { font-weight: 700; text-align: center; color: #2b2f38; margin-bottom: 10px; line-height: 1.6; }
        .terms-intro { margin-bottom: 12px; }
        .photo-preview {
            width: 90px; height: 110px; object-fit: cover; border-radius: 8px;
            border: 1px solid #eef1f8; background: #f8f9fc;
        }
        .btn-link-secondary { color: #6b7186; font-weight: 600; font-size: 13.5px; text-decoration: none; }
        .btn-link-secondary:hover { color: var(--brand); }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="container">
            <a href="{{ route('student-portal.applications.show', $application->id) }}">
                <i class="bi bi-arrow-left"></i> Back to Application Summary
            </a>
        </div>
    </div>

    <div class="page-wrap">

        @if(session('success'))
            <div class="alert alert-success" style="border-radius:12px;">{{ session('success') }}</div>
        @endif
        @if(session('status'))
            <div class="alert alert-info" style="border-radius:12px;">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" style="border-radius:12px;">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card-box">
            <h1>Formulir Pendaftaran</h1>
            <p class="subtitle">
                {{ $application->university->name ?? '-' }} &middot; {{ $application->application_no }}
            </p>
        </div>

        <form method="POST" action="{{ route('student-portal.applications.form.update', $application->id) }}" enctype="multipart/form-data">
            @csrf

            <div class="card-box">
                <div class="section-title">Personal Information</div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Surname</label>
                        <input type="text" name="surname" class="form-control" value="{{ old('surname', $formDetail->surname ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Given Name</label>
                        <input type="text" name="given_name" class="form-control" value="{{ old('given_name', $formDetail->given_name ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Chinese Name (if any)</label>
                        <input type="text" name="chinese_name" class="form-control" value="{{ old('chinese_name', $formDetail->chinese_name ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Photo</label>
                        <div class="d-flex align-items-center gap-3">
                            @if($formDetail && $formDetail->photo_path)
                                <img src="{{ asset($formDetail->photo_path) }}" class="photo-preview" alt="Current photo">
                            @endif
                            <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept=".jpg,.jpeg">
                        </div>
                        @error('photo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach(['Male' => 'Male', 'Female' => 'Female'] as $value => $labelText)
                                <option value="{{ $value }}" {{ old('gender', $formDetail->gender ?? '') === $value ? 'selected' : '' }}>{{ $labelText }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nationality</label>
                        <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $formDetail->nationality ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Passport No.</label>
                        <input type="text" name="passport_no" class="form-control" value="{{ old('passport_no', $formDetail->passport_no ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Passport Expiry Date</label>
                        <input type="date" name="passport_expiry_date" class="form-control" value="{{ old('passport_expiry_date', optional($formDetail?->passport_expiry_date)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Telephone No.</label>
                        <input type="text" name="telephone_no" class="form-control" value="{{ old('telephone_no', $formDetail->telephone_no ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', optional($formDetail?->date_of_birth)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Place of Birth</label>
                        <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $formDetail->place_of_birth ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hobby</label>
                        <input type="text" name="hobby" class="form-control" value="{{ old('hobby', $formDetail->hobby ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Parents Name (Father &amp; Mother)</label>
                        <input type="text" name="parents_name" class="form-control" value="{{ old('parents_name', $formDetail->parents_name ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parents Phone Number (Father &amp; Mother)</label>
                        <input type="text" name="parents_phone" class="form-control" value="{{ old('parents_phone', $formDetail->parents_phone ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Parents Occupation (Father/Mother)</label>
                        <input type="text" name="parents_occupation" class="form-control" value="{{ old('parents_occupation', $formDetail->parents_occupation ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-mail Address</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $formDetail->email ?? '') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Home Address and Zipcode</label>
                        <textarea name="home_address" class="form-control" rows="2">{{ old('home_address', $formDetail->home_address ?? '') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Religion</label>
                        <input type="text" name="religion" class="form-control" value="{{ old('religion', $formDetail->religion ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Highest Degree Obtained</label>
                        <input type="text" name="highest_degree_obtained" class="form-control" value="{{ old('highest_degree_obtained', $formDetail->highest_degree_obtained ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Field of Study in China</label>
                        <input type="text" name="field_of_study_in_china" class="form-control" value="{{ old('field_of_study_in_china', $formDetail->field_of_study_in_china ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Financial Support Will Be Provided By</label>
                        <input type="text" name="financial_support_by" class="form-control" value="{{ old('financial_support_by', $formDetail->financial_support_by ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Scholarship / Self Sponsored</label>
                        <select name="sponsorship_type" class="form-select">
                            <option value="">-- Select --</option>
                            <option value="scholarship" {{ old('sponsorship_type', $formDetail->sponsorship_type ?? '') === 'scholarship' ? 'selected' : '' }}>Scholarship</option>
                            <option value="self_sponsored" {{ old('sponsorship_type', $formDetail->sponsorship_type ?? '') === 'self_sponsored' ? 'selected' : '' }}>Self Sponsored</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-box">
                <div class="section-title">Education Background</div>
                <p class="text-muted" style="font-size:13px;margin-top:-8px;">
                    Start from Elementary School, Junior High, Senior High / University.
                </p>

                <div id="eduRows"></div>

                <button type="button" class="btn-add-row" id="btnAddEduRow">
                    <i class="bi bi-plus-lg"></i> Add Row
                </button>
            </div>

            <div class="card-box">
                <div class="section-title">Terms &amp; Conditions</div>
                <div class="terms-box">
                    {{-- S&K tiga bahasa (ID / EN / 中文), hardcode sesuai teks dari user 7 Okt 2026. --}}
                    <div class="terms-title">TERMS &amp; CONDITIONS<br>KETENTUAN DAN SYARAT PENDAFTARAN KULIAH<br><span lang="zh">留学申请条款与条件</span></div>
                    <div class="terms-intro">
                        <p lang="id">Dengan melakukan pendaftaran melalui INA STUDY, calon mahasiswa/i menyatakan telah membaca, memahami, dan menyetujui seluruh ketentuan berikut.</p>
                        <p lang="en">By registering through INA STUDY, the prospective student confirms that they have read, understood, and agreed to all of the following terms and conditions.</p>
                        <p lang="zh">通过 INA STUDY 提交申请，即表示申请人已阅读、理解并同意以下所有条款与条件。</p>
                    </div>
                    <ol>
                        <li>
                            <strong>Layanan Pendaftaran / Registration Services / 申请服务</strong>
                            <p lang="id">INA STUDY sebagai agen pendidikan berkewajiban membantu proses pendaftaran calon mahasiswa/i ke universitas yang diminati, sesuai dengan program studi, persyaratan, dan ketentuan yang berlaku di universitas terkait.</p>
                            <p lang="en">INA STUDY, as an education agency, is responsible for assisting prospective students with the application process to their chosen university, in accordance with the relevant academic program, admission requirements, and university regulations.</p>
                            <p lang="zh">INA STUDY 作为教育中介机构，负责协助申请人申请其意向大学，并根据相关专业、入学要求及大学规定提供申请服务。</p>
                        </li>
                        <li>
                            <strong>Beasiswa / Scholarship / 奖学金</strong>
                            <p lang="id">INA STUDY dapat memberikan informasi dan membantu proses pengajuan beasiswa. Namun, INA STUDY tidak menjamin bahwa calon mahasiswa/i akan memperoleh beasiswa. Keputusan pemberian beasiswa sepenuhnya merupakan kewenangan universitas atau pihak pemberi beasiswa.</p>
                            <p lang="en">INA STUDY may provide information and assistance regarding scholarship applications. However, INA STUDY does not guarantee that any prospective student will be awarded a scholarship. The decision to grant a scholarship is solely at the discretion of the relevant university or scholarship provider.</p>
                            <p lang="zh">INA STUDY 可提供奖学金相关信息并协助申请人进行奖学金申请。但 INA STUDY 不保证申请人一定能够获得奖学金。奖学金的最终授予决定权归相关大学或奖学金提供方所有。</p>
                        </li>
                        <li>
                            <strong>Pendampingan Kedatangan / Arrival Assistance / 抵达协助</strong>
                            <p lang="id">INA STUDY akan memberikan bantuan dan pendampingan kepada mahasiswa/i pada saat kedatangan di China, termasuk penjemputan dan/atau pendampingan menuju universitas, sesuai dengan layanan yang telah ditentukan.</p>
                            <p lang="en">INA STUDY will provide assistance and support to students upon their arrival in China, including airport pick-up and/or transportation assistance to the university, in accordance with the applicable services.</p>
                            <p lang="zh">INA STUDY 将根据所提供的服务，为学生抵达中国后提供协助与陪同服务，包括机场接机及/或前往大学的相关协助。</p>
                        </li>
                        <li>
                            <strong>Penitipan Barang / Personal Belongings / 个人物品</strong>
                            <p lang="id">Calon mahasiswa/i maupun mahasiswa/i tidak diperkenankan menitipkan barang pribadi, dokumen, uang, atau barang berharga lainnya kepada tim INA STUDY maupun pihak yang ditunjuk oleh INA STUDY. INA STUDY tidak bertanggung jawab atas kehilangan atau kerusakan barang yang dititipkan.</p>
                            <p lang="en">Prospective students and students are not permitted to entrust personal belongings, documents, cash, or other valuables to INA STUDY staff or any party designated by INA STUDY. INA STUDY shall not be responsible for any loss of or damage to items that have been entrusted to such parties.</p>
                            <p lang="zh">申请人及学生不得将个人物品、文件、现金或其他贵重物品交由 INA STUDY 工作人员或 INA STUDY 指定的任何人员保管。对于因委托保管而产生的物品遗失或损坏，INA STUDY 不承担责任。</p>
                        </li>
                        <li>
                            <strong>Kesehatan dan Keselamatan / Health and Safety / 健康与安全</strong>
                            <p lang="id">INA STUDY tidak bertanggung jawab atas kondisi kesehatan maupun keselamatan calon mahasiswa/i atau mahasiswa/i di luar cakupan layanan pendampingan yang diberikan, termasuk sebelum keberangkatan, selama perjalanan, maupun setelah proses pelepasan, kecuali dalam lingkup layanan resmi yang secara khusus menjadi tanggung jawab INA STUDY.</p>
                            <p lang="en">INA STUDY shall not be responsible for the health or safety of prospective students or students outside the scope of the assistance services provided, including prior to departure, during the journey, or after the handover, except for matters specifically covered by INA STUDY’s official services.</p>
                            <p lang="zh">对于申请人或学生在 INA STUDY 所提供的陪同服务范围之外发生的健康或安全问题，INA STUDY 不承担责任，包括出发前、行程期间及交接完成后的相关情况，但属于 INA STUDY 官方服务明确责任范围内的事项除外。</p>
                        </li>
                        <li>
                            <strong>Kehilangan dan Tindakan Kriminal / Loss and Criminal Incidents / 财物遗失及刑事事件</strong>
                            <p lang="id">INA STUDY tidak bertanggung jawab atas kehilangan barang, kerugian, atau kejadian yang berkaitan dengan tindakan kriminal yang dialami calon mahasiswa/i atau mahasiswa/i selama perjalanan maupun selama berada di China, sepanjang kejadian tersebut berada di luar kendali dan tanggung jawab INA STUDY.</p>
                            <p lang="en">INA STUDY shall not be responsible for any loss of belongings, financial loss, or criminal incidents experienced by prospective students or students during their journey or while in China, to the extent that such incidents are beyond the control and responsibility of INA STUDY.</p>
                            <p lang="zh">对于申请人或学生在行程期间或在中国期间发生的财物遗失、经济损失或刑事事件，如相关事件超出 INA STUDY 的控制及责任范围，INA STUDY 不承担责任。</p>
                        </li>
                        <li>
                            <strong>Pembayaran Registration Fee / Registration Fee Payment / 报名费支付</strong>
                            <p lang="id">Calon mahasiswa/i wajib melakukan pembayaran Registration Fee maksimal 3 (tiga) hari setelah menyerahkan formulir pendaftaran kepada INA STUDY. Proses pendaftaran dapat dilanjutkan setelah pembayaran diterima dan dikonfirmasi oleh INA STUDY.</p>
                            <p lang="en">Prospective students are required to pay the Registration Fee within a maximum of 3 (three) days after submitting the registration form to INA STUDY. The application process may proceed once the payment has been received and confirmed by INA STUDY.</p>
                            <p lang="zh">申请人须在向 INA STUDY 提交申请表后最迟 3（三）日内支付报名费（Registration Fee）。在 INA STUDY 收到并确认付款后，申请流程方可继续进行。</p>
                        </li>
                        <li>
                            <strong>Registration Fee – Non-Refundable / Registration Fee – Non-Refundable / 报名费不可退款</strong>
                            <p lang="id">Registration Fee bersifat non-refundable dan tidak dapat dikembalikan, dialihkan maupun ditukarkan dalam bentuk voucher atau bentuk kompensasi lainnya, dengan alasan apa pun.</p>
                            <p lang="en">The Registration Fee is non-refundable and may not be refunded, transferred, converted into a voucher, or exchanged for any other form of compensation for any reason.</p>
                            <p lang="zh">报名费（Registration Fee）一经支付，不予退款，且无论任何原因，均不得转让、兑换为代金券或其他形式的补偿。</p>
                        </li>
                        <li>
                            <strong>Pengurusan Visa / Visa Application / 签证办理</strong>
                            <p lang="id">Mahasiswa/i yang menggunakan layanan INA STUDY wajib melakukan proses pengurusan visa melalui INA STUDY, sesuai dengan prosedur dan ketentuan yang berlaku.</p>
                            <p lang="en">Students using INA STUDY’s services are required to process their visa application through INA STUDY, in accordance with the applicable procedures and regulations.</p>
                            <p lang="zh">使用 INA STUDY 服务的学生须通过 INA STUDY 办理签证，并遵守相关办理流程及规定。</p>
                        </li>
                        <li>
                            <strong>Pemesanan Tiket Pesawat / Flight Ticket Booking / 机票预订</strong>
                            <p lang="id">Mahasiswa/i yang mengikuti program melalui INA STUDY wajib melakukan pemesanan tiket pesawat melalui INA STUDY, sesuai dengan ketentuan keberangkatan dan layanan yang berlaku.</p>
                            <p lang="en">Students participating in a program through INA STUDY are required to book their flight tickets through INA STUDY, in accordance with the applicable departure arrangements and service terms.</p>
                            <p lang="zh">通过 INA STUDY 参加相关项目的学生须通过 INA STUDY 预订机票，并遵守相关出发安排及服务规定。</p>
                        </li>
                        <li>
                            <strong>Surat Penerimaan Universitas / University Admission Letter / 大学录取通知书</strong>
                            <p lang="id">Calon mahasiswa/i yang memenuhi persyaratan dan dinyatakan diterima oleh universitas berhak memperoleh Surat Penerimaan/Admission Letter dari universitas yang bersangkutan.</p>
                            <p lang="en">Prospective students who meet the applicable requirements and are successfully admitted by the university shall be entitled to receive an Admission Letter issued by the relevant university.</p>
                            <p lang="zh">符合相关要求并成功获得大学录取的申请人，有权获得由相关大学出具的录取通知书（Admission Letter）。</p>
                        </li>
                        <li>
                            <strong>Konsultasi Pendidikan / Education Consultation / 教育咨询</strong>
                            <p lang="id">Calon mahasiswa/i yang telah melakukan pendaftaran melalui INA STUDY berhak memperoleh konsultasi pendidikan tanpa biaya tambahan sampai dengan proses penerimaan di universitas, sesuai dengan layanan konsultasi yang disediakan oleh INA STUDY.</p>
                            <p lang="en">Prospective students who have registered through INA STUDY are entitled to receive education consultation at no additional cost throughout the university application process until admission, in accordance with the consultation services provided by INA STUDY.</p>
                            <p lang="zh">通过 INA STUDY 完成报名的申请人，有权在大学申请及录取过程中获得免费的教育咨询服务，具体以 INA STUDY 所提供的咨询服务范围为准。</p>
                        </li>
                    </ol>
                    <div class="terms-intro mt-2">
                        <p lang="id">Dengan melakukan pendaftaran melalui INA STUDY, calon mahasiswa/i menyatakan bahwa seluruh informasi yang diberikan adalah benar dan lengkap serta menyatakan telah membaca, memahami, dan menyetujui seluruh Terms &amp; Conditions yang berlaku.</p>
                        <p lang="en">By registering through INA STUDY, the prospective student confirms that all information provided is true and complete, and acknowledges that they have read, understood, and agreed to all applicable Terms &amp; Conditions.</p>
                        <p lang="zh">通过 INA STUDY 提交申请，即表示申请人确认所提供的信息真实、完整，并确认已阅读、理解并同意所有适用的条款与条件。</p>
                    </div>
                </div>

                <div class="form-check mt-3">
                    <input class="form-check-input @error('terms_accepted') is-invalid @enderror" type="checkbox" name="terms_accepted" value="1" id="termsAccepted" required
                        {{ old('terms_accepted', $formDetail && $formDetail->terms_accepted_at ? '1' : '') ? 'checked' : '' }}>
                    <label class="form-check-label" for="termsAccepted" style="font-size:14px;">
                        Saya telah membaca, memahami, dan menyetujui seluruh Terms &amp; Conditions di atas.
                        <span class="d-block text-muted" style="font-size:12.5px;">I have read, understood, and agree to all Terms &amp; Conditions above. / 我已阅读、理解并同意以上所有条款与条件。</span>
                    </label>
                    @error('terms_accepted')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn-brand w-100">
                <i class="bi bi-arrow-right-circle"></i> Save &amp; Continue to Upload Documents
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('student-portal.applications.show', $application->id) }}" class="btn-link-secondary">
                View Application Summary
            </a>
        </div>

    </div>

    <template id="eduRowTemplate">
        <div class="edu-row">
            <button type="button" class="btn-remove-row" title="Remove"><i class="bi bi-x-lg"></i></button>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Level</label>
                    <select class="form-select edu-level" name="">
                        <option value="">-- Select --</option>
                        @foreach(\App\Models\ApplicationEducationBackground::LEVELS as $levelKey => $levelLabel)
                            <option value="{{ $levelKey }}">{{ $levelLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">School / Institution</label>
                    <input type="text" class="form-control edu-school" name="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control edu-location" name="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year Start</label>
                    <input type="text" class="form-control edu-year-start" name="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year End</label>
                    <input type="text" class="form-control edu-year-end" name="">
                </div>
            </div>
        </div>
    </template>

    @php
        // FIX (10 September 2026) -- dipindah ke sini (bukan inline di dalam
        // @json() di bawah) karena @json() Blade membelah argumennya dengan
        // explode(',', ...) TANPA sadar tanda kurung/kurung siku bersarang --
        // array literal multi-baris dengan banyak koma di dalam @json()
        // langsung dulu bikin hasil compile-nya rusak ("Unclosed '[' ...")
        // dan 500 di server. Dengan dihitung dulu jadi 1 variabel PHP biasa,
        // argumen yang masuk ke @json() di bawah tidak punya koma di level
        // atas lagi, jadi aman.
        $existingEducationArray = $educationBackgrounds->map(fn ($row) => [
            'level' => $row->level,
            'school_name' => $row->school_name,
            'location' => $row->location,
            'year_start' => $row->year_start,
            'year_end' => $row->year_end,
        ])->values();
    @endphp

    <script>
        // FASE 3 -- "add row" Education Background. Setiap baris di-clone
        // dari <template> di atas, lalu name atributnya diisi ulang
        // (education[INDEX][field]) tiap kali reindexEduRows() dipanggil
        // (dipicu oleh add/remove) supaya urutan index selalu rapat mulai
        // dari 0 -- server (ApplicationFormController::update()) melewati
        // baris yang semua kolomnya kosong, jadi index yang bolong pun
        // sebenarnya aman, tapi dirapikan di sini biar konsisten.
        const eduRowsContainer = document.getElementById('eduRows');
        const eduRowTemplate = document.getElementById('eduRowTemplate');

        const existingEducation = @json($existingEducationArray);

        function addEduRow(data) {
            data = data || {};
            const fragment = eduRowTemplate.content.cloneNode(true);
            const rowEl = fragment.querySelector('.edu-row');

            rowEl.querySelector('.edu-level').value = data.level || '';
            rowEl.querySelector('.edu-school').value = data.school_name || '';
            rowEl.querySelector('.edu-location').value = data.location || '';
            rowEl.querySelector('.edu-year-start').value = data.year_start || '';
            rowEl.querySelector('.edu-year-end').value = data.year_end || '';

            rowEl.querySelector('.btn-remove-row').addEventListener('click', function () {
                rowEl.remove();
                reindexEduRows();
            });

            eduRowsContainer.appendChild(rowEl);
        }

        function reindexEduRows() {
            const rows = eduRowsContainer.querySelectorAll('.edu-row');
            rows.forEach(function (row, index) {
                row.querySelector('.edu-level').name = 'education[' + index + '][level]';
                row.querySelector('.edu-school').name = 'education[' + index + '][school_name]';
                row.querySelector('.edu-location').name = 'education[' + index + '][location]';
                row.querySelector('.edu-year-start').name = 'education[' + index + '][year_start]';
                row.querySelector('.edu-year-end').name = 'education[' + index + '][year_end]';
            });
        }

        document.getElementById('btnAddEduRow').addEventListener('click', function () {
            addEduRow();
            reindexEduRows();
        });

        if (existingEducation.length > 0) {
            existingEducation.forEach(function (row) {
                addEduRow(row);
            });
        } else {
            // Form baru (belum pernah diisi) -- mulai dengan 1 baris kosong
            // supaya siswa langsung lihat contoh kolomnya, bukan area kosong
            // total.
            addEduRow();
        }
        reindexEduRows();
    </script>

</body>

</html>
