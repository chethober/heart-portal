<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>راهنمای سامانه اکوکاردیوگرافی | Help Center</title>
    <link type="text/css" rel="stylesheet" href="/legacy/fonts.css">
    <link type="text/css" rel="stylesheet" href="/legacy/navbar.css">
    <style>
        :root {
            --primary: #3182ce;
            --primary-hover: #2b6cb0;
            --bg-main: #f7fafc;
            --card-bg: #ffffff;
            --text-main: #2d3748;
            --text-muted: #718096;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Vazirmatn', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            line-height: 1.8;
            margin: 12px;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        @media (min-width: 768px) {
            body {
                margin: 16px;
            }
        }

        .help-container {
            width: 100%;
            max-width: 1200px;
            box-sizing: border-box;
            margin: 0 auto;
        }

        .help-header {
            background: linear-gradient(135deg, #2b6cb0 0%, #3182ce 100%);
            color: white;
            padding: 32px 20px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(49, 130, 206, 0.18);
            margin-bottom: 24px;
            text-align: center;
        }

        @media (min-width: 768px) {
            .help-header {
                padding: 44px 30px;
                margin-bottom: 30px;
            }
        }

        .help-header h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        @media (min-width: 768px) {
            .help-header h1 {
                font-size: 28px;
            }
        }

        .help-header p {
            font-size: 15px;
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (min-width: 768px) {
            .help-grid {
                gap: 24px;
                margin-bottom: 30px;
            }
        }

        .help-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        @media (min-width: 768px) {
            .help-card {
                padding: 24px;
            }
        }

        .help-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: #ebf8ff;
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .help-card h3 {
            font-size: 17px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
        }

        .help-card p, .help-card ul {
            font-size: 14px;
            color: var(--text-muted);
        }

        .help-card ul {
            list-style: none;
            padding: 0;
            margin-top: 10px;
        }

        .help-card ul li {
            position: relative;
            padding-right: 20px;
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .help-card ul li::before {
            content: "•";
            color: var(--primary);
            font-weight: bold;
            font-size: 18px;
            position: absolute;
            right: 0;
            top: -2px;
        }

        .faq-section {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        @media (min-width: 768px) {
            .faq-section {
                padding: 30px;
            }
        }

        .faq-section h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1a202c;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 10px;
        }

        .faq-item {
            margin-bottom: 20px;
        }

        .faq-item:last-child {
            margin-bottom: 0;
        }

        .faq-question {
            font-weight: 700;
            font-size: 15px;
            color: #2b6cb0;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .faq-answer {
            font-size: 14px;
            color: var(--text-main);
            padding-right: 24px;
            line-height: 1.7;
        }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            margin-top: 28px;
            margin-bottom: 20px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            min-height: 44px;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        @media (max-width: 640px) {
            .quick-actions {
                flex-direction: column;
            }
            .btn-action {
                width: 100%;
            }
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: #edf2f7;
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }
    </style>
</head>
<body>
    <?= view('clinical.navbar')->render() ?>

    <div class="help-container">
        <div class="help-header">
            <h1>راهنمای جامع سامانه ثبت و مدیریت گزارش‌های اکوکاردیوگرافی</h1>
            <p>راهنمای استفاده از امکانات، ثبت اطلاعات بیمار، جستجو، و تولید گزارش تخصصی اکو</p>
        </div>

        <div class="help-grid">
            <div class="help-card">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        <path d="M9 14l2 2 4-4"></path>
                    </svg>
                </div>
                <h3>۱. ثبت فرم اکو (Echo Form)</h3>
                <p>در این بخش می‌توانید اطلاعات بالینی و پارامترهای اکوکاردیوگرافی بیمار را ثبت کنید:</p>
                <ul>
                    <li>مشخصات فردی و بالینی (کد ملی، نام، سن، قد، وزن، BSA و فشار خون).</li>
                    <li>ارزیابی بطن چپ (LV) و بطن راست (RV).</li>
                    <li>ارزیابی دهلیزها (LA / RA) و وضعیت دریچه‌ها.</li>
                    <li>ارزیابی IVC، SPAP و پریکارد (پوشش هوشمند مقادیر پیش‌فرض).</li>
                </ul>
            </div>

            <div class="help-card">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <h3>۲. جستجو و مدیریت سوابق (Patient Echos)</h3>
                <p>امکان دسترسی سریع به گزارش‌های قبلی بیماران:</p>
                <ul>
                    <li>جستجوی سریع با وارد کردن کد ملی در نوار بالای صفحه یا صفحه سوابق.</li>
                    <li>مشاهده لیست تمام اکوهای ثبت‌شده برای یک بیمار به همراه تاریخ.</li>
                    <li>امکان ویرایش مجدد، حذف یا مشاهده گزارش کامل هر اکو.</li>
                </ul>
            </div>

            <div class="help-card">
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
                <h3>۳. مشاهده گزارش و چاپ (Detailed Report)</h3>
                <p>تولید فرم استاندارد چاپی برای ارایه به بیمار یا بایگانی:</p>
                <ul>
                    <li>فرمت‌بندی خودکار متن و برجسته‌سازی (Underline) یافته‌های غیرنرمال.</li>
                    <li>دکمه چاپ (Print Report) با مقیاس مناسب و استاندارد ۱۲۰٪.</li>
                    <li>امکان ذخیره به صورت PDF از طریق مرورگر.</li>
                </ul>
            </div>
        </div>

        <div class="faq-section">
            <h2>پرسش‌های متداول (FAQ)</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    محاسبه هوشمند IVC چگونه کار می‌کند؟
                </div>
                <div class="faq-answer">
                    در حالت پیش‌فرض (گزینه auto)، اگر اندازه IVC کمتر یا مساوی ۲۱ میلی‌متر باشد، وضعیت آن به صورت normal size گزارش می‌شود و اگر بیشتر از ۲۱ میلی‌متر باشد به صورت dilated ثبت می‌گردد.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    چگونه گزارش اکو را چاپ یا به PDF تبدیل کنم؟
                </div>
                <div class="faq-answer">
                    پس از مشاهده گزارش کامل بیمار در صفحه Detailed Report، بر روی دکمه آبی رنگ Print Report در پایین صفحه کلیک کنید. در پنجره پرینت مرورگر، پرینتر یا گزینه Save as PDF را انتخاب کنید.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    چگونه سوابق اکوهای قبلی یک بیمار را پیدا کنم؟
                </div>
                <div class="faq-answer">
                    کافی است کد ملی بیمار را در کادر Quick Search در نوار بالای صفحه وارد کرده و روی Search کلیک کنید یا به صفحه Patient Echos بروید.
                </div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="<?= e(route('studies.create')) ?>" class="btn-action btn-primary">
                ورود به فرم ثبت اکو
            </a>
            <a href="<?= e(route('patients.index')) ?>" class="btn-action btn-secondary">
                جستجوی سوابق بیماران
            </a>
        </div>
    </div>
</body>
</html>
