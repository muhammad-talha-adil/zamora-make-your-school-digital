<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        School::create([
            'name' => 'MJS Grammar School',
            'slogan' => 'Nurturing Character, Building Futures',
            'logo_path' => '/sample-logo.png',
            'website_enabled' => true,

            // Hero
            'hero_headline' => 'Welcome to MJS Grammar School',
            'hero_subtext' => 'A caring learning community where every student is known, challenged, and supported — from Early Years through Secondary.',
            'hero_cta_primary_text' => 'Apply for Admission',
            'hero_cta_primary_url' => '/admissions',
            'hero_cta_secondary_text' => 'Explore Academics',
            'hero_cta_secondary_url' => '/academics',
            'hero_illustration_seed' => 'school-campus',

            // Trust logos (kept for future use, not shown on a single-school site)
            'trust_logos' => [],

            // About
            'mission_statement' => 'To provide a nurturing environment where every student is guided to discover their potential, build strong character, and grow into a confident, responsible citizen.',
            'vision_statement' => 'To be a school where academic excellence and personal growth go hand in hand, preparing students for life, not just for exams.',
            'values' => [
                ['icon' => 'heart', 'title' => 'Student First', 'description' => 'Every decision, from timetable to teaching method, starts with what is best for the student.'],
                ['icon' => 'shield', 'title' => 'Discipline with Care', 'description' => 'We hold students to high standards while treating every child with patience and respect.'],
                ['icon' => 'zap', 'title' => 'Learning Beyond Books', 'description' => 'Sports, arts, and community service are as core to us as the classroom.'],
            ],
            'leadership_team' => [
                ['name' => 'Dr. Ayesha Malik', 'role' => 'Principal', 'bio' => '18 years in school leadership, with a focus on student wellbeing and academic standards.', 'photo_url' => 'https://picsum.photos/seed/principal-1/400/400'],
                ['name' => 'Mr. Faisal Ahmed', 'role' => 'Vice Principal', 'bio' => 'Oversees daily academics and discipline across all grade levels.', 'photo_url' => 'https://picsum.photos/seed/principal-2/400/400'],
                ['name' => 'Ms. Hina Raza', 'role' => 'Head of Academics', 'bio' => 'Leads curriculum planning and teacher training.', 'photo_url' => 'https://picsum.photos/seed/principal-3/400/400'],
            ],
            'stats' => [
                ['label' => 'Students', 'value' => 1200, 'suffix' => '+'],
                ['label' => 'Teachers', 'value' => 85, 'suffix' => '+'],
                ['label' => 'Years of Excellence', 'value' => 22, 'suffix' => ''],
                ['label' => 'Classrooms', 'value' => 48, 'suffix' => ''],
            ],
            'history_timeline' => [
                ['year' => '2003', 'title' => 'School Founded', 'description' => 'Opened its doors with two classrooms and 40 students, driven by a mission to bring quality education to the community.'],
                ['year' => '2008', 'title' => 'Secondary Section Added', 'description' => 'Expanded to offer classes through Secondary level as the founding batch grew up.'],
                ['year' => '2013', 'title' => 'New Campus Building', 'description' => 'Moved to a larger, purpose-built campus with science labs, a library, and sports grounds.'],
                ['year' => '2018', 'title' => 'Digital Classrooms', 'description' => 'Introduced smart boards and computer labs across all grade levels.'],
                ['year' => '2024', 'title' => 'Milestone: 1,200+ Students', 'description' => 'Crossed 1,200 enrolled students across all sections, with a growing waiting list every year.'],
            ],

            // Contact
            'contact_address' => '123 Education Street, Model Town, Lahore, Pakistan',
            'contact_phone' => '+92 300 1234567',
            'contact_email' => 'info@mjsgrammar.edu.pk',
            'contact_hours' => 'Mon-Sat 8am-2pm',
            'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.123456789!2d-74.006!3d40.7128!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a!2s123%20Education%20St!5e0!3m2!1sen!2sus!4v1234567890',
            'social_links' => [
                ['platform' => 'facebook', 'url' => 'https://facebook.com/mjsgrammar', 'icon' => 'facebook'],
                ['platform' => 'instagram', 'url' => 'https://instagram.com/mjsgrammar', 'icon' => 'instagram'],
                ['platform' => 'youtube', 'url' => 'https://youtube.com/@mjsgrammar', 'icon' => 'youtube'],
            ],

            // SEO
            'meta_title' => 'MJS Grammar School - Admissions Open',
            'meta_description' => 'MJS Grammar School offers quality education from Early Years to Secondary. Admissions are now open — visit our campus or apply online.',
            'og_image_path' => '/og-image.png',

            // Home page
            'home_features' => [
                ['icon' => 'UserGroupIcon', 'title' => 'Small Class Sizes', 'description' => 'Low student-to-teacher ratios mean every child gets individual attention and is known by name.', 'seed' => 'small-classes'],
                ['icon' => 'AcademicCapIcon', 'title' => 'Experienced Faculty', 'description' => 'Our teachers are subject specialists who bring years of classroom experience and genuine care.', 'seed' => 'faculty'],
                ['icon' => 'BeakerIcon', 'title' => 'Modern Labs & Library', 'description' => 'Well-equipped science labs, computer labs, and a well-stocked library support hands-on learning.', 'seed' => 'labs-library'],
                ['icon' => 'TrophyIcon', 'title' => 'Sports & Co-curricular', 'description' => 'Inter-house competitions, arts, music, and clubs help students grow beyond the classroom.', 'seed' => 'sports'],
                ['icon' => 'ShieldCheckIcon', 'title' => 'Safe Campus', 'description' => 'A secure, well-maintained campus with attentive staff so parents can trust their child is safe.', 'seed' => 'safe-campus'],
                ['icon' => 'HeartIcon', 'title' => 'Individual Attention', 'description' => 'We track every student\'s progress closely and support them with the care of a close-knit community.', 'seed' => 'attention'],
            ],

            // Academics page
            'academics_hero_headline' => 'A curriculum built for every stage of growth',
            'academics_hero_subtext' => 'From Early Years to Secondary, our academic program balances strong fundamentals with the skills students need for what comes next.',
            'academics_programs' => [
                [
                    'title' => 'Strong academic foundations',
                    'description' => 'Our teachers follow a structured curriculum in Mathematics, Sciences, Languages, and Social Studies, with regular assessments to track every student\'s progress and give timely support where it\'s needed.',
                    'features' => ['National curriculum alignment', 'Continuous classroom assessment', 'Term-end report cards', 'Remedial support for struggling students', 'Subject-specialist teachers'],
                    'image_seed' => 'academics-classroom',
                ],
                [
                    'title' => 'More than textbooks',
                    'description' => 'Sports, arts, debate, and community service run alongside academics, helping students build confidence, teamwork, and interests beyond the classroom.',
                    'features' => ['Inter-house sports competitions', 'Art & music programs', 'Debate and public speaking clubs', 'Science and robotics club', 'Annual community service drive'],
                    'image_seed' => 'academics-activities',
                ],
            ],
            'academics_faq' => [
                ['question' => 'What grade levels does the school offer?', 'answer' => 'We offer classes from Early Years through Secondary, with a consistent curriculum framework that carries students from their first day through graduation.'],
                ['question' => 'How are students assessed?', 'answer' => 'Students are assessed through regular class tests, term exams, and continuous classroom evaluation. Report cards are issued at the end of every term with detailed subject-wise performance.'],
                ['question' => 'What co-curricular activities are available?', 'answer' => 'Sports, arts, debate, science club, and community service programs run throughout the year alongside the academic calendar.'],
                ['question' => 'What is the student-to-teacher ratio?', 'answer' => 'We keep class sizes small so every teacher can give individual attention — typically no more than 30 students per class.'],
            ],

            // Admissions page
            'admissions_hero_headline' => 'Join our school community',
            'admissions_hero_subtext' => 'Admissions are open for the upcoming academic year. Submit an enquiry and our admissions team will guide you through every step.',
            'admission_steps' => [
                ['step' => 'Step 1', 'title' => 'Submit an enquiry', 'description' => 'Fill out the enquiry form with your child\'s details and the class you\'re applying for. Our admissions team will get back to you within 2 business days.'],
                ['step' => 'Step 2', 'title' => 'Campus visit & assessment', 'description' => 'Visit the campus for a tour and a simple age-appropriate assessment to understand your child\'s current level.'],
                ['step' => 'Step 3', 'title' => 'Document verification', 'description' => 'Submit birth certificate, previous school records (if any), and passport-size photographs for verification.'],
                ['step' => 'Step 4', 'title' => 'Confirmation & enrollment', 'description' => 'Once approved, complete the fee payment and enrollment formalities to secure your child\'s seat for the academic year.'],
            ],
            'admissions_faq' => [
                ['question' => 'What documents are required for admission?', 'answer' => 'Birth certificate, previous school leaving certificate and report card (for transfer students), passport-size photographs, and immunization records.'],
                ['question' => 'Is there an entrance test?', 'answer' => 'A simple, age-appropriate assessment is conducted to understand your child\'s current academic level. It is not a pass/fail exam.'],
                ['question' => 'When does the admission process open?', 'answer' => 'Admissions typically open at the start of the calendar year, but we accept enquiries and mid-year transfers throughout the year subject to seat availability.'],
                ['question' => 'Do you offer scholarships or fee concessions?', 'answer' => 'Yes, need-based and merit-based concessions are available. Please mention this in your enquiry and our admissions team will guide you through the process.'],
                ['question' => 'Can siblings apply together?', 'answer' => 'Yes, and sibling fee concessions apply automatically once both admissions are confirmed.'],
            ],
        ]);
    }
}
