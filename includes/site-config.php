<?php
declare(strict_types=1);

$site = [
    'name' => 'AuthorJuanJose.io',
    'author' => 'Author Juan Jose',
    'year' => (int)date('Y'),
];

$main_navigation = [
    ['label' => 'Home', 'url' => '/'],
    ['label' => 'Fiction', 'url' => '/fiction'],
    ['label' => 'Non-Fiction', 'url' => '/non-fiction'],
    ['label' => 'About', 'url' => '/about'],
    ['label' => 'Journal', 'url' => '/journal'],
    ['label' => 'Media', 'url' => '/media'],
    ['label' => 'Events', 'url' => '/events'],
    ['label' => 'Contact', 'url' => '/contact'],
    ['label' => 'ARC Reader Club', 'url' => '/arc-reader-club'],
];

$arc_navigation = [
    ['label' => 'ARC Reader Club', 'url' => '/arc-reader-club'],
    ['label' => 'Join the Club', 'url' => '/arc-reader-club/join'],
    ['label' => 'How It Works', 'url' => '/arc-reader-club/how-it-works'],
    ['label' => 'Honors & Distinctions', 'url' => '/arc-reader-club/honors-and-distinctions'],
    ['label' => 'FAQ', 'url' => '/arc-reader-club/faq'],
    ['label' => 'Login', 'url' => '/arc-reader-club/login'],
];

$member_navigation = [
    ['label' => 'Dashboard', 'url' => '/arc-reader-club/dashboard'],
    ['label' => 'Current Missions', 'url' => '/arc-reader-club/current-missions'],
    ['label' => 'Submit Review', 'url' => '/arc-reader-club/submit-review'],
    ['label' => 'My Distinctions', 'url' => '/arc-reader-club/my-distinctions'],
    ['label' => 'Archive Record', 'url' => '/arc-reader-club/archive-record'],
    ['label' => 'Logout', 'url' => '/arc-reader-club/logout'],
];
