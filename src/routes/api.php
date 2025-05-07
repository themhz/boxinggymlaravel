<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::get('/homepage', function () {
    return response()->json([
        'hero' => [
            'headline' => 'Welcome to IronFist Gym',
            'subtext' => 'Train your body and mind with top-tier martial arts coaching.',
            'cta_text' => 'Join a Class',
            'cta_link' => 'classes.html',
            'alt_text' => 'Book Free Trial',
            'alt_link' => 'appointment.html',
        ],
        'classes' => [
            [
                'title' => 'Boxing',
                'description' => 'Train like a champion with our boxing classes.',
                'image' => '/templates/boxinggym/boxinggym1/assets/img/boxing3.jpg',
            ],
            [
                'title' => 'Muay Thai',
                'description' => 'Master the art of striking with Muay Thai.',
                'image' => '/templates/boxinggym/boxinggym1/assets/img/muaythai.jpg',
            ],
            [
                'title' => 'Brazilian Jiu-Jitsu',
                'description' => 'Get fit with our expert-led BJJ training.',
                'image' => '/templates/boxinggym/boxinggym1/assets/img/ziozitso2.jpg',
            ],
        ],
        'carousel' => [
            [
                'video' => '/templates/boxinggym/boxinggym1/assets/video/video1.mp4',
                'title' => 'Boxing Classes',
                'description' => 'Train like a champion with our boxing classes.',
            ],
            [
                'video' => '/templates/boxinggym/boxinggym1/assets/video/video2.mp4',
                'title' => 'Muay Thai Training',
                'description' => 'Master the art of striking with our Muay Thai training.',
            ],
            [
                'video' => '/templates/boxinggym/boxinggym1/assets/video/video3.mp4',
                'title' => 'Brazilian Jiu-Jitsu',
                'description' => 'Learn self-defense and improve your fitness with BJJ.',
            ],
        ],
        'about' => [
            'title' => 'About Our Gym',
            'description' => 'At IronFist Gym, we help you achieve your fitness and martial arts goals. Our instructors and community make it the perfect place to grow.',
            'button_text' => 'Learn More',
            'button_link' => 'about.html',
        ],
        'testimonials' => [
            [
                'quote' => '"IronFist Gym changed my life! Amazing trainers and community."',
                'author' => 'John Doe',
            ],
            [
                'quote' => '"I love the variety of classes and friendly atmosphere!"',
                'author' => 'Jane Smith',
            ],
        ]
    ]);
});


Route::get('/about', function () {
    return response()->json([
        'hero' => [
            'title' => 'About IronFist Gym',
            'subtitle' => 'Where Passion Meets Discipline'
        ],
        'story' => [
            'title' => 'Our Story',
            'intro' => 'Founded in 2015, IronFist Gym was born out of a passion for martial arts and a desire to create a community where everyone feels welcome. Whether you\'re a beginner or a seasoned fighter, we’re here to help you achieve your goals.',
            'mission' => 'Our gym is more than just a place to train—it’s a family. We believe in the power of martial arts to transform lives, build confidence, and foster discipline.',
            'image' => 'https://placehold.co/600x400'
        ],
        'team' => [
            [
                'name' => 'John Doe',
                'role' => 'Head Boxing Coach',
                'description' => 'With over 10 years of experience, John has trained champions and beginners alike.',
                'image' => 'https://placehold.co/300x300'
            ],
            [
                'name' => 'Jane Smith',
                'role' => 'Muay Thai Specialist',
                'description' => 'Jane brings a wealth of knowledge and a passion for teaching Muay Thai.',
                'image' => 'https://placehold.co/300x300'
            ],
            [
                'name' => 'Mike Johnson',
                'role' => 'BJJ Black Belt',
                'description' => 'Mike is dedicated to helping students master the art of Brazilian Jiu-Jitsu.',
                'image' => 'https://placehold.co/300x300'
            ]
        ],
        'why_choose_us' => [
            [
                'title' => 'Expert Instructors',
                'description' => 'Our coaches are highly trained and passionate about helping you succeed.'
            ],
            [
                'title' => 'State-of-the-Art Facilities',
                'description' => 'Train in a clean, modern, and fully equipped gym.'
            ],
            [
                'title' => 'Supportive Community',
                'description' => 'Join a welcoming community that motivates and inspires you.'
            ]
        ]
    ]);
});


Route::get('/classes', function () {
    return response()->json([
        'hero' => [
            'title' => 'Our Classes',
            'subtitle' => 'Find the perfect class to achieve your fitness and martial arts goals.'
        ],
        'classes' => [
            [
                'title' => 'Boxing',
                'description' => 'Train like a champion with our boxing classes. Improve your strength, speed, and technique.',
                'image' => 'https://placehold.co/400x300'
            ],
            [
                'title' => 'Muay Thai',
                'description' => 'Master the art of striking with our Muay Thai training programs.',
                'image' => 'https://placehold.co/400x300'
            ],
            [
                'title' => 'Brazilian Jiu-Jitsu',
                'description' => 'Learn self-defense and improve your fitness with our BJJ classes.',
                'image' => 'https://placehold.co/400x300'
            ]
        ],
        'schedule' => [
            'Monday' => [
                ['class' => 'Boxing', 'time' => '6:00 PM - 7:30 PM'],
                ['class' => 'BJJ', 'time' => '5:00 PM - 6:30 PM'],
            ],
            'Tuesday' => [
                ['class' => 'Muay Thai', 'time' => '7:00 PM - 8:30 PM'],
            ],
            'Wednesday' => [
                ['class' => 'Boxing', 'time' => '6:00 PM - 7:30 PM'],
                ['class' => 'BJJ', 'time' => '5:00 PM - 6:30 PM'],
            ],
            'Thursday' => [
                ['class' => 'Muay Thai', 'time' => '7:00 PM - 8:30 PM'],
            ],
            'Friday' => [
                ['class' => 'Boxing', 'time' => '6:00 PM - 7:30 PM'],
            ],
            'Saturday' => [
                ['class' => 'BJJ', 'time' => '10:00 AM - 11:30 AM'],
            ],
            'Sunday' => [
                ['class' => 'Rest Day', 'time' => null]
            ]
        ],
        'cta' => [
            'title' => 'Ready to Join?',
            'subtitle' => 'Sign up for a free trial class today and experience the IronFist Gym difference.',
            'button' => 'Book a Free Trial'
        ]
    ]);
});



Route::get('/pricing', function () {
    return response()->json([
        'hero' => [
            'title' => 'Membership Plans',
            'subtitle' => 'Choose the plan that fits your goals and budget.'
        ],
        'plans' => [
            [
                'name' => 'Basic Plan',
                'price' => '$50/month',
                'features' => [
                    'Access to 1 class per week',
                    'Open gym hours',
                    'Locker access',
                ]
            ],
            [
                'name' => 'Standard Plan',
                'price' => '$80/month',
                'features' => [
                    'Access to 3 classes per week',
                    'Open gym hours',
                    'Locker access',
                    'Free gym T-shirt',
                ]
            ],
            [
                'name' => 'Premium Plan',
                'price' => '$120/month',
                'features' => [
                    'Unlimited classes',
                    'Open gym hours',
                    'Locker access',
                    'Free gym T-shirt',
                    'Personal training session (1/month)',
                ]
            ]
        ],
        'offers' => [
            [
                'title' => 'Student Discount',
                'description' => 'Get 10% off any membership plan with a valid student ID.'
            ],
            [
                'title' => 'Family Plan',
                'description' => 'Sign up with a family member and each get 15% off your membership.'
            ]
        ],
        'cta' => [
            'title' => 'Ready to Join?',
            'subtitle' => 'Sign up for a free trial class today and experience the [Your Gym Name] difference.'
        ]
    ]);
});



Route::get('/appointments/availability', function () {
    return response()->json([
        "09:00" => [ "Mon" => true,  "Tue" => true,  "Wed" => false, "Thu" => true,  "Fri" => true,  "Sat" => false, "Sun" => false ],
        "10:00" => [ "Mon" => true,  "Tue" => false, "Wed" => true,  "Thu" => true,  "Fri" => false, "Sat" => true,  "Sun" => false ],
        "11:00" => [ "Mon" => false, "Tue" => true,  "Wed" => true,  "Thu" => false, "Fri" => true,  "Sat" => true,  "Sun" => false ],
        "12:00" => [ "Mon" => true,  "Tue" => true,  "Wed" => true,  "Thu" => true,  "Fri" => true,  "Sat" => false, "Sun" => false ]
    ]);
});


Route::post('/appointments/book', function (Request $request) {
    // For now, just log the appointment data to Laravel logs
    Log::info('Appointment booked:', $request->all());

    return response()->json([
        'status' => 'success',
        'message' => 'Appointment received (mock response)',
        'data' => $request->all()
    ]);
});


Route::get('/contact-info', function () {
    return response()->json([
        'hero' => [
            'title' => 'Contact Us',
            'subtitle' => 'We\'d love to hear from you! Reach out for inquiries, feedback, or to schedule a visit.',
        ],
        'location' => [
            'address' => '123 Gym Street, City, State, ZIP Code',
            'phone' => '(123) 456-7890',
            'email' => 'info@yourgym.com',
            'map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.8354345093747!2d144.95373531531615!3d-37.816279742021665!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6ad642af0f11fd81%3A0xf577d44e7c30b4df!2sYour%20Gym%20Name!5e0!3m2!1sen!2sus!4v1633023226785!5m2!1sen!2sus', // your real URL here
        ]
    ]);
});

Route::post('/contact-message', function (Request $request) {
    Log::info('Contact Form Submission', $request->all());

    return response()->json(['message' => 'Message received successfully!']);
});


Route::post('/signup-preview', function (Request $request) {
    Log::info('Signup form data:', $request->all());
    return response()->json(['status' => 'ok']);
});



// routes/api.php
Route::post('/login', function (Request $request) {
    $email = $request->input('email');
    $password = $request->input('password');

    // Default hardcoded credentials (for testing only)
    if ($email === 'themhz@gmail.com' && $password === '526996') {
        return response()->json(['success' => true, 'message' => 'Login successful']);
    }

    return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
});
