<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    .registration-box {
    border: 3px solid #f4c542;
    border-radius: 12px;
    padding: 35px 25px 25px;
    margin-top: 20px;
}

.registration-box legend {
    display: table;
    margin: 0 auto;
    padding: 8px 24px;
    background: #ac0606;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    border: 2px solid #f4c542;
    border-radius: 30px;
    text-align: center;
}
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-sm">
               <div class="card-header text-center text-white py-5"
                    style="
                        background: url('{{ asset('frontend/img/bg1.jpg') }}') center center / cover no-repeat;
                    ">
                    <img src="{{ asset('frontend/img/Logo.png') }}" alt="Logo" class="mb-3" style="width: 130px;">
                    <p class="lead text-danger" style="font-size: 1.1rem;"><strong>YOU ARE INVITED TO</strong></p>
                    <img src="{{ asset('frontend/img/text.png') }}" alt="Logo" class="mb-3" style="width: 270px;">
                    <p class="lead fw-bold mb-3" style="font-size: 1.1rem; color: #ac0606;">
                        Departure Briefing ~ Alumni Testimonies ~ Yule Ceremony
                    </p>
                   
                </div>

                <div class="card-body p-4">

                    {{-- Success --}}
                    @if (session('success'))
                        <div class="alert alert-success text-center fw-semibold">
                            ✅ {{ session('success') }}
                        </div>
                    @endif

                    {{-- Error --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="d-flex justify-content-center mb-3">
                        <!-- <span style="background: #ac0606; color: #fff; padding: 8px 24px; border-radius: 30px; font-weight: 700;">
                            Registration Form
                        </span> -->
                        <span style="background: #ac0606; color: #fff; padding: 8px 24px; border-radius: 30px; font-weight: 700; font-family: 'Cormorant Garamond', serif; font-size: 1.5rem;">
                            Registration Form
                        </span>
                    </div>
                    <form action="{{ route('invitation.store') }}" method="POST">
                        @csrf
                        <fieldset class="registration-box">

                        <small style="color: #ac0606; margin-bottom: 3rem; display: block;">Fill in the details below, and the QR code will be sent to your <b>WhatsApp</b></small>
                     
                        <div class="row mb-4 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                Name <span class="text-danger">*</span>
                            </label>

                            <div class="col-md-8">
                                <input type="text"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    placeholder="Enter your full name">

                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                WhatsApp<span class="text-danger">*</span>
                            </label>
                          
                            <div class="col-md-8">
                                <input type="number"
                                    name="handphone"
                                    class="form-control @error('handphone') is-invalid @enderror"
                                    value="{{ old('handphone') }}"
                                    placeholder="Example : 081234567890">

                                @error('handphone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>



                         <!-- <div class="row mb-4 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                University / Institution <span class="text-danger">*</span>
                            </label>
                           
                            <div class="col-md-8">
                                <input type="text"
                                    name="university"
                                    class="form-control @error('university') is-invalid @enderror"
                                    value="{{ old('university') }}"
                                    placeholder="Enter your University / Institution">

                                @error('university')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div> -->
                        <div class="row mb-3 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                               University / Institution
                            </label>

                          

                            <div class="col-md-8">
                                <select name="university"
                                        class="form-select @error('university') is-invalid @enderror">

                                    <option value="">-- Select University --</option>

                                    <option value="University of Aeronautics & Astronautics"
                                        {{ old('university') == 'University of Aeronautics & Astronautics' ? 'selected' : '' }}>
                                        University of Aeronautics & Astronautics
                                    </option>

                                    <option value="Nanjing Normal University"
                                        {{ old('university') == 'Nanjing Normal University' ? 'selected' : '' }}>
                                        Nanjing Normal University
                                    </option>

                                    <option value="Nanjing University Institute of Science and Technology"
                                        {{ old('university') == 'Nanjing University Institute of Science and Technology' ? 'selected' : '' }}>
                                        Nanjing University Institute of Science and Technology
                                    </option>

                                    <option value="Nanjing Technology University"
                                        {{ old('university') == 'Nanjing Technology University' ? 'selected' : '' }}>
                                        Nanjing Technology University
                                    </option>

                                    <option value="China Pharmaceutical University"
                                        {{ old('university') == 'China Pharmaceutical University' ? 'selected' : '' }}>
                                        China Pharmaceutical University
                                    </option>

                                    <option value="Nanjing Medical University"
                                        {{ old('university') == 'Nanjing Medical University' ? 'selected' : '' }}>
                                        Nanjing Medical University
                                    </option>

                                    <option value="Shanghai Jiaotong University"
                                        {{ old('university') == 'Shanghai Jiaotong University' ? 'selected' : '' }}>
                                        Shanghai Jiaotong University
                                    </option>

                                    <option value="Harbin Institute of Technology (shenzhen)"
                                        {{ old('university') == 'Harbin Institute of Technology (shenzhen)' ? 'selected' : '' }}>
                                        Harbin Institute of Technology (shenzhen)
                                    </option>

                                    <option value="Zhejiang University"
                                        {{ old('university') == 'Zhejiang University' ? 'selected' : '' }}>
                                        Zhejiang University
                                    </option>

                                    <option value="Shanghai University"
                                        {{ old('university') == 'Shanghai University' ? 'selected' : '' }}>
                                        Shanghai University
                                    </option>

                                    <option value="The Chinese University of Hongkong"
                                        {{ old('university') == 'The Chinese University of Hongkong' ? 'selected' : '' }}>
                                        The Chinese University of Hongkong
                                    </option>

                                    <option value="Jiangsu Shipping Collage"
                                        {{ old('university') == 'Jiangsu Shipping Collage' ? 'selected' : '' }}>
                                        Jiangsu Shipping Collage
                                    </option>

                                    <option value="South China University of Technology"
                                        {{ old('university') == 'South China University of Technology' ? 'selected' : '' }}>
                                        South China University of Technology
                                    </option>

                                    <option value="East China Normal University"
                                        {{ old('university') == 'East China Normal University' ? 'selected' : '' }}>
                                        East China Normal University
                                    </option>

                                    <option value="Zhejiang Normal University"
                                        {{ old('university') == 'Zhejiang Normal University' ? 'selected' : '' }}>
                                        Zhejiang Normal University
                                    </option>

                                    <option value="Jiangsu Collage of Finance and Accounting"
                                        {{ old('university') == 'Jiangsu Collage of Finance and Accounting' ? 'selected' : '' }}>
                                        Jiangsu Collage of Finance and Accounting
                                    </option>

                                    <option value="Yule's Students"
                                        {{ old('university') == ' Yule s Students' ? 'selected' : '' }}>
                                        Yule's Students
                                    </option>

                                    <option value="Panitia"
                                        {{ old('university') == ' Panitia' ? 'selected' : '' }}>
                                        Panitia
                                    </option>

                                     <option value="Special Guest"
                                        {{ old('university') == ' Special Guest' ? 'selected' : '' }}>
                                        Special Guest
                                    </option>

                                   

                                </select>

                                @error('university')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        

                     
                        <div class="row mb-3 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                Program / Major
                            </label>

                          

                            <div class="col-md-8">
                                <select name="program"
                                        class="form-select @error('program') is-invalid @enderror">

                                    <option value="">-- Select Program --</option>

                                    <option value="Bachelor"
                                        {{ old('program') == 'Bachelor' ? 'selected' : '' }}>
                                        Bachelor
                                    </option>

                                    <option value="Master"
                                        {{ old('program') == 'Master' ? 'selected' : '' }}>
                                        Master
                                    </option>

                                    <option value="Chinese Language Program"
                                        {{ old('program') == 'Chinese Language Program' ? 'selected' : '' }}>
                                        Chinese Language Program
                                    </option>

                                    <option value="Diploma"
                                        {{ old('program') == 'Diploma' ? 'selected' : '' }}>
                                        Diploma
                                    </option>

                                </select>

                                @error('program')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                 
                        <div class="row mb-3 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                Number of Attendees
                            </label>

                         

                            <div class="col-md-8">
                                <input type="number"
                                    name="number_of_attendes"
                                    class="form-control @error('number_of_attendes') is-invalid @enderror"
                                    value="{{ old('number_of_attendes', 1) }}"
                                    min="1">

                                @error('number_of_attendes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- <button type="submit" class="btn btn-danger w-100 py-2 fw-semibold text-white">
                            REGISTER & SEND INVITATION TO WHATSAPP
                        </button> -->
                        <button type="submit"
                            class="btn w-100 py-2 fw-semibold text-white"
                            style="background-color: #ac0606; border-color: #ac0606; font-family: 'Cormorant Garamond', serif; font-size: 1rem;">
                        REGISTER & SEND INVITATION TO WHATSAPP
                        </button>
                      </fieldset>

                    </form>

                </div>
                <img src="{{ asset('frontend/img/Logo-uni.png') }}" alt="Logo" class="mb-3" style="">    
                <div class="card-footer text-center text-muted py-3">
                    <small style="font-family: 'Cormorant Garamond', serif;";>&copy; {{ date('Y') }} Inagroup. All rights reserved.</small>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
