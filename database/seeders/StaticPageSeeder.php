<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ApplicationEnvironmentEnum;
use App\Enums\StaticPage\ApplicationTypeEnum;
use Illuminate\Database\Seeder;
use Nizek\StaticPage\Database\Models\StaticPage;

class StaticPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (ApplicationEnvironmentEnum::isRiskyEnvironment()) {
            return;
        }

        $this->seedForApplicationType(ApplicationTypeEnum::CUSTOMER);
        $this->seedForApplicationType(ApplicationTypeEnum::RIDER);

        $this->command->info('Static pages seeded successfully for Customer and Rider apps!');
    }

    private function seedForApplicationType(ApplicationTypeEnum $applicationType): void
    {
        $pages = $this->getStaticPages($applicationType);

        foreach ($pages as $page) {
            $staticPage = StaticPage::query()->updateOrCreate(
                [
                    StaticPage::COLUMN_SLUG => $page['slug'],
                ],
                [
                    StaticPage::COLUMN_TITLE => $page['title'],
                    StaticPage::COLUMN_TITLE_AR => $page['title_ar'],
                    StaticPage::COLUMN_CONTENT => $page['content'],
                    StaticPage::COLUMN_CONTENT_AR => $page['content_ar'],
                    StaticPage::COLUMN_ENABLED => true,
                    'application_type' => $applicationType->value,
                ]
            );

            $this->attachIcon($staticPage, $page['icon']);
        }

        $this->command->info("Static pages seeded for {$applicationType->value} app.");
    }

    private function attachIcon(StaticPage $staticPage, string $iconFileName): void
    {
        $iconPath = public_path("images/static-pages/{$iconFileName}");

        if (! file_exists($iconPath)) {
            $this->command->warn("Icon not found: {$iconPath}");

            return;
        }

        // Clear existing media and add new one
        $staticPage->clearMediaCollection(StaticPage::IMAGE_COLLECTION_NAME);
        $staticPage->addMedia($iconPath)
            ->preservingOriginal()
            ->toMediaCollection(StaticPage::IMAGE_COLLECTION_NAME);
    }

    /**
     * @return array<int, array{slug: string, title: string, title_ar: string, content: string, content_ar: string, icon: string}>
     */
    private function getStaticPages(ApplicationTypeEnum $applicationType): array
    {
        $appPrefix = $applicationType->value;

        return [
            [
                'slug' => "{$appPrefix}-contact-us",
                'title' => 'Contact Us',
                'title_ar' => 'اتصل بنا',
                'content' => $this->getContactUsContent(),
                'content_ar' => $this->getContactUsContentAr(),
                'icon' => 'phone.png',
            ],
            [
                'slug' => "{$appPrefix}-about-us",
                'title' => 'About Us',
                'title_ar' => 'من نحن',
                'content' => $this->getAboutUsContent(),
                'content_ar' => $this->getAboutUsContentAr(),
                'icon' => 'about.png',
            ],
            [
                'slug' => "{$appPrefix}-terms-and-conditions",
                'title' => 'Terms & Conditions',
                'title_ar' => 'الشروط والأحكام',
                'content' => $this->getTermsContent(),
                'content_ar' => $this->getTermsContentAr(),
                'icon' => 'terms.png',
            ],
            [
                'slug' => "{$appPrefix}-privacy-policy",
                'title' => 'Privacy Policy',
                'title_ar' => 'سياسة الخصوصية',
                'content' => $this->getPrivacyPolicyContent(),
                'content_ar' => $this->getPrivacyPolicyContentAr(),
                'icon' => 'privacy.png',
            ],
        ];
    }

    private function getContactUsContent(): string
    {
        return <<<'HTML'
<h2>Get in Touch</h2>
<p>We're here to help! If you have any questions, concerns, or feedback, please don't hesitate to reach out to us.</p>
<h3>Contact Information</h3>
<ul>
    <li><strong>Email:</strong> support@ebh.com</li>
    <li><strong>Phone:</strong> +965 1234 5678</li>
    <li><strong>Address:</strong> Kuwait City, Kuwait</li>
</ul>
<h3>Business Hours</h3>
<p>Sunday - Thursday: 9:00 AM - 6:00 PM</p>
<p>Friday - Saturday: Closed</p>
HTML;
    }

    private function getContactUsContentAr(): string
    {
        return <<<'HTML'
<h2>تواصل معنا</h2>
<p>نحن هنا للمساعدة! إذا كان لديك أي أسئلة أو استفسارات أو ملاحظات، يرجى عدم التردد في التواصل معنا.</p>
<h3>معلومات الاتصال</h3>
<ul>
    <li><strong>البريد الإلكتروني:</strong> support@ebh.com</li>
    <li><strong>الهاتف:</strong> 5678 1234 965+</li>
    <li><strong>العنوان:</strong> مدينة الكويت، الكويت</li>
</ul>
<h3>ساعات العمل</h3>
<p>الأحد - الخميس: 9:00 صباحاً - 6:00 مساءً</p>
<p>الجمعة - السبت: مغلق</p>
HTML;
    }

    private function getAboutUsContent(): string
    {
        return <<<'HTML'
<h2>About EBH</h2>
<p>EBH is a leading medical transportation service provider in Kuwait, dedicated to offering safe, reliable, and comfortable transportation for patients and individuals with special medical needs.</p>
<h3>Our Mission</h3>
<p>To provide exceptional medical transportation services that prioritize patient comfort, safety, and dignity while ensuring timely and professional service delivery.</p>
<h3>Our Vision</h3>
<p>To be the most trusted medical transportation company in Kuwait, known for our commitment to excellence, compassion, and innovation in healthcare mobility solutions.</p>
<h3>Our Values</h3>
<ul>
    <li><strong>Safety First:</strong> Patient safety is our top priority</li>
    <li><strong>Compassion:</strong> We treat every patient with care and respect</li>
    <li><strong>Reliability:</strong> On-time service you can count on</li>
    <li><strong>Professionalism:</strong> Trained and certified transportation specialists</li>
</ul>
HTML;
    }

    private function getAboutUsContentAr(): string
    {
        return <<<'HTML'
<h2>عن EBH</h2>
<p>EBH هي شركة رائدة في مجال خدمات النقل الطبي في الكويت، مكرسة لتوفير خدمات نقل آمنة وموثوقة ومريحة للمرضى والأفراد ذوي الاحتياجات الطبية الخاصة.</p>
<h3>مهمتنا</h3>
<p>تقديم خدمات نقل طبي استثنائية تعطي الأولوية لراحة المريض وسلامته وكرامته مع ضمان تقديم خدمة في الوقت المناسب ومهنية.</p>
<h3>رؤيتنا</h3>
<p>أن نكون شركة النقل الطبي الأكثر ثقة في الكويت، معروفة بالتزامنا بالتميز والرحمة والابتكار في حلول التنقل الصحي.</p>
<h3>قيمنا</h3>
<ul>
    <li><strong>السلامة أولاً:</strong> سلامة المريض هي أولويتنا القصوى</li>
    <li><strong>الرحمة:</strong> نتعامل مع كل مريض بعناية واحترام</li>
    <li><strong>الموثوقية:</strong> خدمة في الوقت المحدد يمكنك الاعتماد عليها</li>
    <li><strong>الاحترافية:</strong> متخصصون مدربون ومعتمدون في النقل</li>
</ul>
HTML;
    }

    private function getTermsContent(): string
    {
        return <<<'HTML'
<h2>Terms & Conditions</h2>
<p>Please read these terms and conditions carefully before using our services.</p>
<h3>1. Acceptance of Terms</h3>
<p>By accessing and using the EBH application, you accept and agree to be bound by these terms and conditions.</p>
<h3>2. Service Description</h3>
<p>EBH provides medical transportation services connecting patients with certified drivers and vehicles equipped for medical transport needs.</p>
<h3>3. User Responsibilities</h3>
<ul>
    <li>Provide accurate booking information</li>
    <li>Be ready at the designated pickup location and time</li>
    <li>Treat drivers and staff with respect</li>
    <li>Comply with all safety instructions</li>
</ul>
<h3>4. Cancellation Policy</h3>
<p>Cancellations made less than 2 hours before the scheduled pickup may incur a cancellation fee.</p>
<h3>5. Payment Terms</h3>
<p>Payment is due at the time of service unless prior arrangements have been made.</p>
<h3>6. Liability</h3>
<p>EBH is committed to providing safe transportation. However, we are not liable for delays caused by traffic, weather, or other circumstances beyond our control.</p>
HTML;
    }

    private function getTermsContentAr(): string
    {
        return <<<'HTML'
<h2>الشروط والأحكام</h2>
<p>يرجى قراءة هذه الشروط والأحكام بعناية قبل استخدام خدماتنا.</p>
<h3>1. قبول الشروط</h3>
<p>من خلال الوصول إلى تطبيق EBH واستخدامه، فإنك تقبل وتوافق على الالتزام بهذه الشروط والأحكام.</p>
<h3>2. وصف الخدمة</h3>
<p>تقدم EBH خدمات النقل الطبي التي تربط المرضى بالسائقين المعتمدين والمركبات المجهزة لاحتياجات النقل الطبي.</p>
<h3>3. مسؤوليات المستخدم</h3>
<ul>
    <li>تقديم معلومات حجز دقيقة</li>
    <li>الاستعداد في موقع ووقت الاستلام المحدد</li>
    <li>التعامل مع السائقين والموظفين باحترام</li>
    <li>الامتثال لجميع تعليمات السلامة</li>
</ul>
<h3>4. سياسة الإلغاء</h3>
<p>قد تترتب رسوم إلغاء على عمليات الإلغاء التي تتم قبل أقل من ساعتين من موعد الاستلام المحدد.</p>
<h3>5. شروط الدفع</h3>
<p>الدفع مستحق في وقت الخدمة ما لم يتم إجراء ترتيبات مسبقة.</p>
<h3>6. المسؤولية</h3>
<p>تلتزم EBH بتوفير نقل آمن. ومع ذلك، لسنا مسؤولين عن التأخيرات الناجمة عن حركة المرور أو الطقس أو الظروف الأخرى الخارجة عن سيطرتنا.</p>
HTML;
    }

    private function getPrivacyPolicyContent(): string
    {
        return <<<'HTML'
<h2>Privacy Policy</h2>
<p>Your privacy is important to us. This policy explains how we collect, use, and protect your information.</p>
<h3>1. Information We Collect</h3>
<ul>
    <li><strong>Personal Information:</strong> Name, phone number, email address</li>
    <li><strong>Location Data:</strong> Pickup and drop-off locations for trip purposes</li>
    <li><strong>Payment Information:</strong> Securely processed through certified payment providers</li>
</ul>
<h3>2. How We Use Your Information</h3>
<ul>
    <li>To provide and improve our transportation services</li>
    <li>To communicate with you about bookings and updates</li>
    <li>To ensure safety and security</li>
    <li>To comply with legal requirements</li>
</ul>
<h3>3. Information Sharing</h3>
<p>We do not sell your personal information. We may share information with:</p>
<ul>
    <li>Drivers assigned to your trips</li>
    <li>Payment processors for transactions</li>
    <li>Law enforcement when legally required</li>
</ul>
<h3>4. Data Security</h3>
<p>We implement industry-standard security measures to protect your data from unauthorized access.</p>
<h3>5. Your Rights</h3>
<p>You have the right to access, correct, or delete your personal information. Contact us at support@ebh.com for any privacy-related requests.</p>
HTML;
    }

    private function getPrivacyPolicyContentAr(): string
    {
        return <<<'HTML'
<h2>سياسة الخصوصية</h2>
<p>خصوصيتك مهمة بالنسبة لنا. توضح هذه السياسة كيف نجمع معلوماتك ونستخدمها ونحميها.</p>
<h3>1. المعلومات التي نجمعها</h3>
<ul>
    <li><strong>المعلومات الشخصية:</strong> الاسم، رقم الهاتف، عنوان البريد الإلكتروني</li>
    <li><strong>بيانات الموقع:</strong> مواقع الاستلام والتوصيل لأغراض الرحلة</li>
    <li><strong>معلومات الدفع:</strong> تتم معالجتها بشكل آمن من خلال مزودي دفع معتمدين</li>
</ul>
<h3>2. كيف نستخدم معلوماتك</h3>
<ul>
    <li>لتقديم وتحسين خدمات النقل لدينا</li>
    <li>للتواصل معك بشأن الحجوزات والتحديثات</li>
    <li>لضمان السلامة والأمن</li>
    <li>للامتثال للمتطلبات القانونية</li>
</ul>
<h3>3. مشاركة المعلومات</h3>
<p>نحن لا نبيع معلوماتك الشخصية. قد نشارك المعلومات مع:</p>
<ul>
    <li>السائقين المعينين لرحلاتك</li>
    <li>معالجي الدفع للمعاملات</li>
    <li>جهات إنفاذ القانون عند الحاجة قانونياً</li>
</ul>
<h3>4. أمان البيانات</h3>
<p>نطبق إجراءات أمنية متوافقة مع معايير الصناعة لحماية بياناتك من الوصول غير المصرح به.</p>
<h3>5. حقوقك</h3>
<p>لديك الحق في الوصول إلى معلوماتك الشخصية أو تصحيحها أو حذفها. تواصل معنا على support@ebh.com لأي طلبات متعلقة بالخصوصية.</p>
HTML;
    }
}
