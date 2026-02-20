<?php 
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ipList[0]);
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

date_default_timezone_set('Asia/Kolkata');
$datevalue = date('Y-m-d H:i:s');
$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$slug = trim($path, '/');

$data = [];

if (!empty($slug)) {
    $data['page_url'] = $slug;
}

$fields = [
    'utm_source'   => 'source',
    'utm_medium'   => 'medium',
    'utm_campaign' => 'campaign',
    'utm_id'       => 'id',
    'utm_content'  => 'content',
    'utm_term'     => 'term',
    'gad_source'   => 'gad_source',
    'gad_campaignid' => 'gad_campaignid',
    'gclid'        => 'gclid'
];

foreach ($fields as $req_key => $data_key) {
    if (!empty($_REQUEST[$req_key])) {
        $data[$data_key] = $_REQUEST[$req_key];
    }
}

if (!empty($_SERVER['HTTP_REFERER'])) {
    $data['referrer'] = $_SERVER['HTTP_REFERER'];
}

$source_value = base64_encode(json_encode($data));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-MV63NHB8CG"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
    
      gtag('config', 'G-MV63NHB8CG');
    </script>
    <!-- Required meta tags -->
    <title>The NEST School – IB & Cambridge School in Chennai</title>
    <meta charset="utf-8">
    <meta name="title" content="The NEST School – IB & Cambridge School in Chennai">
    <meta name="Description" content="Holistic education with IB PYP & Cambridge Curriculum from Pre-KG to Grade 12. Discover admissions, programmes, and our values & culture.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/png" href="favicon.png" />
    <!-- Font -->
    <link rel="stylesheet" href="https://use.typekit.net/fuu5sjt.css">
    <!-- Font Awesome -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <!-- Owl Carousel -->
    <link href="./assets/css/owl.carousel.min.css" rel="stylesheet">
    <!-- Style -->
    <link href="./assets/css/style.css" rel="stylesheet">
    <link href="./assets/css/form-style.css" rel="stylesheet">
    <!-- Image Lightbox -->
    <link href="./assets/css/simple-lightbox.min.css" rel="stylesheet" />
    
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-KBGD2LFR');</script>
    <!-- End Google Tag Manager -->
    
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1384387343234976');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=1384387343234976&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
        
</head>

<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src=""https://www.googletagmanager.com/ns.html?id=GTM-KBGD2LFR""
    height=""0"" width=""0"" style=""display:none;visibility:hidden""></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <div class="nac-left">
                <a class="navbar-brand" href="#"><img src="./assets/images/nest-logo.png" alt="thenestschool-logo" class="iim-logo"></a>
                <a class="" href="#"><img src="./assets/images/location.svg" alt="location" class=""></a>
            </div>
            <div class="nav-right">
                <img class="nav-rit-logos" src="./assets/images/logos.png" alt="logos">
            </div>
        </div>
    </nav>  
    
    <!-- Banner -->
    <section class="iim-banner-sec">
        <div class="desktop-banner">
            <a href="#" data-toggle="modal" data-target="#enquiry-for-admission"  data-button="banner" id="enquiry-for-admission-id" class="cta-item track-btn"><img src="./assets/images/nestschool-campaign.webp" alt="nest-banner"></a>
        </div>
      
        <div class="mobile-banner">
            <img src="./assets/images/nestcampaign-mbil-banner.png" alt="nest-banner">
        </div>
    </section>
    
    <!-- Logos on mobile -->
    <div class="logo-group-mobile">
        <img src="./assets/images/logos.png" alt="">
    </div>
    
    <!-- Tab Grades -->
    <section class="grade-tab-sec">
        <div class="grades-filter" >
            <div class="grades_filter_container">
                <div class="grades_filter">
                    Select Grade
                </div>
    
                <ul class="grades_filter_menu" style="display: none;">
                    <li data-grade-year="prekg" class="active">Pre-KG to UKG</li>
                    <li data-grade-year="grade1-5" class="">Grade 1-5</li>
                    <li data-grade-year="grade6-8" class="">Grade 6-8</li>
                    <li data-grade-year="grade9-10" class="">Grade 9-10</li>
                    <li data-grade-year="grade11-12" class="">Grade 11-12</li>
                </ul>
            </div>
        </div>
    </section> 

    <section class="features-sec" aria-label="Program features list">
        <img src="./assets/images/sketch-sun.svg" alt="assets" class="floating-image sketch-sun">
        <img src="./assets/images/sketch-jupiter.svg" alt="assets" class="floating-image sketch-jupiter">
        <div class="container" id="grades-container">
            <!--tab looping content start-->
            <!--Tab 1-->
            <div class="grade-content" data-grade-year="prekg">
                <div class="ph-title">
                    <h2 class="grade-name">EYP (Pre-KG - UKG) Program Highlights</h2>
                </div>
                <div class="wrap-form-featr">
                    <div class="features-wrap">
                      <!-- Column 1 -->
                      <div class="timeline" aria-hidden="false">
                        <div class="feature">
                          <h4>1. One School. One Journey. Pre-KG to Grade 12</h4>
                          <p>Begins with the IB Early Years Programme at age 3, progresses into IB Primary (Grades 1–5), transitions to Cambridge IGCSE (Grades 6–10), and finishes with IBDP or AS/A Levels (Grades 11–12), all on one campus.</p>
                          <p>An internationally benchmarked curriculum that grows with your child from their first step.</p>
                        </div>
                        <div class="feature">
                          <h4>2. Hyper-Personalised Learning from Day One</h4>
                          <p>1:12 teacher–child ratio.</p>
                          <p>Every child known by name, learning pace, and style.</p>
                          <p>Progress tracked through the GenNEST Growth Journal.</p>
                        </div>
                        <div class="feature">
                          <h4>3. Teaching Excellence, Systematically Built</h4>
                          <p>All facilitators are IB-trained and complete 370+ hours of professional development annually.</p>
                          <p>Focus areas include pedagogy, early childhood development, subject mastery, and classroom methods, ensuring consistency and care across every classroom.</p>
                        </div>
                      </div>
                
                      <!-- Column 2 -->
                      <div class="timeline" aria-hidden="false">
                         <div class="feature">
                          <h4>4. A University-Level Learning Ecosystem</h4>
                          <p>100+ advanced labs and future-facing learning environments.</p>
                          <p>Includes robotics, aero-modelling, AR/VR zones, innovation studios, entrepreneurship centres, and incubation spaces, ready to support growth from age 3 to 18.</p>
                        </div>
                        <div class="feature">
                          <h4>5. World-Class Sports Infrastructure</h4>
                          <p>15-acre campus in Kodambakkam with a cricket ground, archery, pickleball, paddleball, and outdoor play zones.</p>
                          <p>Sports are valued as much as academics, with dedicated time and facilities for every child to master physical skills.</p>
                        </div>
                        <div class="feature no-line-mbil">
                          <h4>6. Learning That Goes Beyond Classrooms</h4>
                          <p>Children explore across four learning centres: Sensory, Science, Fantasy & Drama, and Math.</p>
                          <p>Indoor–outdoor classrooms, green mounds, sensory gardens, and a live terrarium nurture creativity and curiosity beyond four walls.</p>
                        </div>
                      </div>
                    </div>
                </div>
                <div class="cta-in-prgtab"><a href="#" data-toggle="modal" data-button="EYP" data-target="#enquiry-for-admission-kgs" id="enquiry-for-admission-id" class="cta-item banner-cta track-btn">Enquire for Admission</a></div>
            </div>
            
            <!--Tab 2-->
            <div class="grade-content" data-grade-year="grade1-5">
                <div class="ph-title">
                    <h2 class="grade-name">PYP (Grades 1–5) Program Highlights</h2>
                </div>
                <div class="wrap-form-featr">
                    <div class="features-wrap">
                      <!-- Column 1 -->
                      <div class="timeline" aria-hidden="false">
                        <div class="feature">
                          <h4>1. One School. One Journey. Grade 1 to Grade 12</h4>
                          <p>Your child begins with the globally benchmarked IB Primary Years Programme, transitions into Cambridge IGCSE from Grades 6 to 10, and completes school with a choice of Cambridge AS/A Levels or the IB Diploma Programme. A complete academic journey designed to build inquiry, independence, and global readiness.</p>
                        </div>
                        <div class="feature">
                          <h4>2. Hyper-Personalised Learning That Grows With Your Child</h4>
                          <p>1:12 teacher-child ratio.</p>
                          <p>Every child known by name, learning pace, and style.</p>
                          <p>Individual progress tracked through the GenNEST Growth Journal.</p>
                        </div>
                        <div class="feature">
                          <h4>3. Teaching Excellence, Systematically Built</h4>
                          <p>All facilitators are IB-trained and complete 370 hours of professional development annually.</p>
                          <p>Focus areas include pedagogy, early childhood development, subject mastery, and classroom methods - ensuring consistency and care across every classroom.</p>
                        </div>
                      </div>
                
                      <!-- Column 2 -->
                      <div class="timeline" aria-hidden="false">
                         <div class="feature">
                          <h4>4. University-Level Learning Ecosystem From Primary School</h4>
                          <p>100+ advanced labs and future-facing learning environments.</p>
                          <p>Includes robotics, aero-modelling, AR/VR zones, innovation studios, entrepreneurship centers, and incubation spaces - ready to support growth until age 18.</p>
                        </div>
                        <div class="feature">
                          <h4>5. World-Class Sports and Activity Infrastructure</h4>
                          <p>A 15-acre campus in Kodambakkam with a cricket ground, archery, pickleball, paddleball, and outdoor play zones.</p>
                          <p>Sports are valued as much as academics, with dedicated time and facilities for every child to master physical skills.</p>
                        </div>
                        <div class="feature no-line-mbil">
                          <h4>6. Learning That Goes Beyond Classrooms</h4>
                          <p>Children engage in regular design thinking, sustainability, and digital intelligence projects.</p>
                          <p>Clubs, advocacy hours, and learner expeditions bring passion, real-world exposure, and purpose into everyday learning.</p>
                          <p>Independence is nurtured through reflection corners, outdoor classrooms, and hands-on skill building.</p>
                        </div>
                      </div>
                    </div>
                </div>
                <div class="cta-in-prgtab"><a href="#" data-toggle="modal" data-button="grade1-5" data-target="#enquiry-for-admission-grade1to5" id="enquiry-for-admission-id" class="cta-item banner-cta track-btn">Enquire for Admission</a></div>
            </div>
            
            <!--Tab 3-->
            <div class="grade-content" data-grade-year="grade6-8">
                <div class="ph-title">
                    <h2 class="grade-name">Cambridge Lower Secondary (Grades 6–8) Program Highlights</h2>
                </div>
                <div class="wrap-form-featr">
                    <div class="features-wrap">
                      <!-- Column 1 -->
                      <div class="timeline" aria-hidden="false">
                        <div class="feature">
                          <h4>1. One School. One Journey. Grades 6 to 12</h4>
                          <p>Cambridge Lower Secondary curriculum builds academic depth in core subjects with subject choice from Grade 9.</p>
                          <p>The journey moves seamlessly through IGCSE and culminates in IB Diploma Programme or Cambridge AS/A Levels.</p>                          
                        </div>
                        <div class="feature">
                          <h4>2. Hyper-Personalized Learning That Builds Early Clarity</h4>
                          <p>Know Your Child model ensures 1:3 mentoring, enrichment hours in Math and Science, and SOAR goal setting.</p>
                          <p>Student-led GenNEST Growth Journals track academic, personal, and career exploration progress.</p>
                        </div>
                        <div class="feature">
                          <h4>3. Teaching Excellence. Consistently Delivered</h4>
                          <p>Facilitators complete 370+ hours of annual training in pedagogy, adolescent development, subject mastery, and classroom methods.</p>
                          <p>Learning quality stays consistent, with strong focus on conceptual understanding and practical application.</p>
                        </div>
                      </div>
                
                      <!-- Column 2 -->
                      <div class="timeline" aria-hidden="false">
                         <div class="feature">
                          <h4>4. A University-Level Learning Ecosystem</h4>
                          <p>Access to 100+ advanced labs including Robotics, Aero Modelling, AR/VR, IoT, VLSI, and Advanced Manufacturing.</p>
                          <p>Dedicated spaces for innovation, research, and prototyping support student-led projects and career discovery.</p>
                        </div>
                        <div class="feature">
                          <h4>5. World-Class Sports and Activity Infrastructure</h4>
                          <p>15-acre campus in Kodambakkam with cricket ground, archery, pickleball, paddleball, athletics track, and open play fields.</p>
                          <p>Physical and team sports are built into the weekly timetable with expert coaches and tournament pathways.</p>
                        </div>
                        <div class="feature no-line-mbil">
                          <h4>6. Real-World Exposure and Future Readiness</h4>
                          <p>Mandatory 2–4 week internships, industry visits, and hands-on entrepreneurship projects with industry mentorship.</p>
                          <p>Career Development Centre (MS-CDC) guides early decisions through psychometric testing and career deep-dives.</p>
                        </div>
                      </div>
                    </div>
                </div>
                <div class="cta-in-prgtab"><a href="#" data-toggle="modal" data-button="grade6-8" data-target="#enquiry-for-admission-grade6to8" id="enquiry-for-admission-id" class="cta-item banner-cta track-btn">Enquire for Admission</a></div>
            </div>
            
            <!--Tab 4-->
            <div class="grade-content" data-grade-year="grade9-10">
                <div class="ph-title">
                    <h2 class="grade-name">Cambridge IGCSE Programme (Grades 9–10) Highlights</h2>
                </div>
                <div class="wrap-form-featr">
                    <div class="features-wrap">
                      <!-- Column 1 -->
                      <div class="timeline" aria-hidden="false">
                        <div class="feature">
                          <h4>1. One School. One Journey. Grade 9 to 12</h4>
                          <p>Your child begins with the internationally respected Cambridge IGCSE, known for academic depth and subject mastery. From here, they transition seamlessly to the IB Diploma Programme or Cambridge AS/A Levels, all on one campus. Future-ready learning with no academic disruptions.</p>
                        </div>
                        <div class="feature">
                          <h4>2. Hyper-Personalised Learning and Mentorship</h4>
                          <p>1:3 mentoring model with a dedicated academic and wellness coach. Students maintain a GenNEST Growth Journal to track goals, achievements, and skills. Progress is reviewed through learner-led conferences, reflection modules, and parent engagement.</p>
                        </div>
                        <div class="feature">
                          <h4>3. Teaching Excellence, Strategically Built</h4>
                          <p>Driven by Cambridge-trained faculty. Each teacher undergoes 370+ hours of professional development annually, including subject depth, research training, technology integration, and assessment strategy, ensuring rigorous preparation and global alignment.</p>
                        </div>
                      </div>
                
                      <!-- Column 2 -->
                      <div class="timeline" aria-hidden="false">
                         <div class="feature">
                          <h4>4. University-Level Learning Ecosystem</h4>
                          <p>100+ advanced labs in robotics, aero-modelling, AR/VR, cybersecurity, and sustainability. Access to research, innovation, and incubation centres. Industry mentors, Wadhwani Foundation, and T-Hub partnerships provide real-world project exposure and startup opportunities.</p>
                        </div>
                        <div class="feature">
                          <h4>5. Real-World Exposure and Global Readiness</h4>
                          <p>Mandatory internships, Deep Dives, learner-led projects, career mapping, Olympiad and SAT prep. Global exposure through university fairs, Model United Nations, TEDxYouth, and international culture experiences</p>
                        </div>
                        <div class="feature no-line-mbil">
                          <h4>6. World-Class Sports and Activity Infrastructure</h4>
                          <p>15-acre campus with cricket ground, archery, pickleball, shooting, athletics track, and multi-sport courts. Sports and movement are integrated into the academic day, building physical skill, discipline, and well-being alongside academic progress.</p>
                        </div>
                      </div>
                    </div>
                </div>
                <div class="cta-in-prgtab"><a href="#" data-toggle="modal" data-button="grade9-10" data-target="#enquiry-for-admission-grade9-10" id="enquiry-for-admission-id" class="cta-item banner-cta track-btn">Enquire for Admission</a></div>
            </div>
            
            <!--Tab 5-->
            <div class="grade-content" data-grade-year="grade11-12">
                <div class="ph-title">
                    <h2 class="grade-name">Cambridge AS & A Levels / IB Diploma Programme (Grades 11–12) Highlights</h2>
                </div>
                <div class="wrap-form-featr">
                    <div class="features-wrap">
                      <!-- Column 1 -->
                      <div class="timeline" aria-hidden="false">
                        <div class="feature">
                          <h4>1. Two Global Curricula. One Launchpad for Top Universities</h4>
                          <p>Students choose between the Cambridge AS/A Levels and the IB Diploma Programme – both globally recognised for academic depth and university readiness.</p>
                          <p>Custom subject pathways across Science, Commerce, Humanities, and Arts allow students to tailor their academic direction to future goals.</p>
                        </div>
                        <div class="feature">
                          <h4>2. Hyper-Personalised Learning and Mentorship</h4>
                          <p>A 1:3 mentoring model with academic and wellness coaches.</p>
                          <p>Each student maintains a GenNEST Growth Journal to track goals, achievements, and skills.</p>
                          <p>Progress is reviewed through learner-led conferences, reflection modules, and personalised roadmap planning.</p>
                        </div>
                        <div class="feature">
                          <h4>3. Teaching Excellence, Strategically Built</h4>
                          <p>All facilitators are Cambridge- or IB-trained and undergo 370+ hours of annual development.</p>
                          <p>Focus areas include subject mastery, research mentorship, university readiness, and assessment design.</p>
                          <p>Ensures global standards and classroom consistency.</p>
                        </div>
                      </div>
                
                      <!-- Column 2 -->
                      <div class="timeline" aria-hidden="false">
                         <div class="feature">
                          <h4>4. University-Level Learning Ecosystem</h4>
                          <p>100+ advanced labs in robotics, aero-modelling, AR/VR, cybersecurity, and sustainability.</p>
                          <p>Access to research centres, incubation hubs, and real industry mentors.</p>
                          <p>Supported by partnerships with the Wadhwani Foundation, T-Hub, and more.</p>
                        </div>
                        <div class="feature">
                          <h4>5. Real-World Exposure and Global Readiness</h4>
                          <p>Structured internships, deep-dive workshops, career mapping, and competitive exam prep (SAT, IELTS, Olympiads).</p>
                          <p>Profile-building through TEDxYouth, MUN, university fairs, and international collaborations.</p>
                          <p>Equips students to stand out in global admissions.</p>
                        </div>
                        <div class="feature no-line-mbil">
                          <h4>6. World-Class Sports and Activity Infrastructure</h4>
                          <p>A 15-acre campus in the heart of Kodambakkam with dedicated sports and co-curricular zones.</p>
                          <p>Cricket ground, archery, pickleball, paddleball, and more – integrated into daily schedules.</p>
                          <p>Students master physical skills alongside academics.</p>
                        </div>
                      </div>
                    </div>
                </div>
                <div class="cta-in-prgtab"><a href="#" data-toggle="modal" data-button="grade11-12" data-target="#enquiry-for-admission-grade11-12" id="enquiry-for-admission-id" class="cta-item banner-cta track-btn">Enquire for Admission</a></div>
            </div>
            
        </div>
    </section>
    
    <!-- Alumni Speak Section -->
    <section class="alumni-sec">
        <img src="./assets/images/curve-rainbow-desk.svg" alt="assets" class="float-img-in-desk">
        <div class="container">
            
            <div class="alumni-wrapper left">
                 <img src="./assets/images/star-sun.webp" alt="assets" class="floating-image star-sun">
                <!--<p class="section-subtitle">Infrastructure</p>-->
                <h3 class="section-title">Infrastructure</h3>
                <!-- Left Carousel -->
                <div class="mobile-testi-btm-adjmt">
  
                  <div id="event-slider" class="owl-carousel owl-theme alumni-left-carousel logo-slider nest-event-slider testi-arrow gallery">


                    <div class="event-image">
                      <a data-url="./assets/images/gallery/gallery-1.webp" rel="rel1">
                        <img alt="" loading="lazy" src="./assets/images/gallery/gallery-1.webp">
                      </a>
                    </div>

                    <div class="event-image">
                      <a data-url="./assets/images/gallery/gallery-2.webp" rel="rel1">
                        <img alt="" loading="lazy" src="./assets/images/gallery/gallery-2.webp">
                      </a>
                    </div>

                    <div class="event-image">
                      <a data-url="./assets/images/gallery/gallery-3.webp" rel="rel1">
                        <img alt="" loading="lazy" src="./assets/images/gallery/gallery-3.webp">
                      </a>
                    </div>

                    <div class="event-image">
                      <a data-url="./assets/images/gallery/gallery-4.webp" rel="rel1">
                        <img alt="" loading="lazy" src="./assets/images/gallery/gallery-4.webp">
                      </a>
                    </div>
                    
                    <div class="event-image">
                      <a data-url="./assets/images/gallery/gallery-5.webp" rel="rel1">
                        <img alt="" loading="lazy" src="./assets/images/gallery/gallery-5.webp">
                      </a>
                    </div>
                    
                    <div class="event-image">
                      <a data-url="./assets/images/gallery/gallery-6.webp" rel="rel1">
                        <img alt="" loading="lazy" src="./assets/images/gallery/gallery-6.webp">
                      </a>
                    </div>
                  </div>

                  <div id="counter" class="counter-pos"></div>
                </div>
            </div>
            
              <div class="alumni-wrapper right">
                 <img src="./assets/images/curve-rainbow.svg" alt="assets" class="floating-image rainbow">
                 <img src="./assets/images/sketch-rocket.svg" alt="assets" class="floating-image sketch-rocket">
                 <h3 class="section-title">From the School Director</h3>
                <!-- Right Carousel -->
                <div class="alumni-right-carousel">
                  <!-- Slide 1 -->
                  <div class="alumni-right-card">
                    <div class="alumni-profile">
                      <img src="./assets/images/nest-director.png" alt="Richard Hillebrand">
                      <div>
                        <h4>Richard Hillebrand</h4>
                        <p class="role">Director, The NEST School</p>
                      </div>
                    </div>
                    <div class="alumni-quote">
                        <p class="acchiv">30+ Years Global Educational Leadership</p>
                        <blockquote>
                          At The NEST School, every detail is intentional. From our curriculum to our campus, everything is built to help children grow with clarity, confidence, and purpose. This is not a school made of disconnected stages. It is a seamless journey from age 3 to 18. Each program prepares your child for what comes next. Not just in academics, but as a thinker, a doer, and a learner for life. Teaching excellence is strengthened through continuous faculty development. Every student receives hyper-personalised mentoring that recognises their pace, strengths, and personality. The campus brings together world-class sports infrastructure, over 100 advanced labs, innovation studios, and incubation centres. These spaces help children explore deeply and grow with direction. The NEST School offers more than education. It gives children the momentum to build the future they want to live in.
                        </blockquote>
                    </div>
                  </div>
                  
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-bar-sec">
        <div class="container">
            <div class="wrap-ctas">
                <div class="connect-icon">
                    <a href="#" data-toggle="modal" data-target="#visit-campus" data-button="schedule" id="visit-campus-id" class="cta-item track-btn">
                        <span class="cta-icon"><img src="./assets/images/schedule.png" alt="Visit Campus Icon"></span>
                        <span class="cta-text">Schedule a Visit</span>
                    </a>
                </div>
                <div class="connect-icon">
                    <a href="#" data-toggle="modal" data-target="#open-house" data-button="open-house" id="open-house-id" class="cta-item track-btn">
                        <span class="cta-icon"><img src="./assets/images/register-openhouse.png" alt="Register for AMA Icon "></span>
                        <span class="cta-text">Register for an Open House</span>
                    </a>
                </div>
                <div class="connect-icon">
                    <a href="#" data-toggle="modal" data-target="#download-brochure" data-button="brochure" id="download-brochure-id" class="cta-item track-btn">
                        <span class="cta-icon"><img src="./assets/images/download-brochure.png" alt="Download Brochure Icon"></span>
                        <span class="cta-text">Download Brochure</span>
                    </a>
                </div>
                <div class="connect-icon">
                     <a href="#" data-toggle="modal" data-target="#enquiry-for-admission" data-button="ADM" id="enquiry-for-admission-id" class="cta-item track-btn">
                        <span class="cta-icon"><img src="./assets/images/apply-online.png" alt="Apply Online Icon"></span>
                        <span class="cta-text">Enquire for Admission</span>
                    </a>
                   
                </div>
                
            </div>
        </div>
    </section>
    
    <!-- Mobile sticky CTA -->
    <a href="#" data-toggle="modal" data-target="#enquiry-for-admission" data-button="mobile" id="enquiry-for-admission-mbl" class="cta-item mobile-sticky-cta track-btn">Enquire for Admission</a>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row footer-wrap">
                <div class="col-12 wrap-logo-sm">
                    <img src="./assets/images/nest-logo.png" class="footer-image">
                    <div class="social_block">
                    		<div class="social_icon"><a href="https://www.facebook.com/TheNESTSchoolChennai/" target="_blank"><img src="./assets/images/fb.svg" alt="facebook"></a></div>
                    		<div class="social_icon"><a href="https://www.instagram.com/the_nest_school/?hl=en" target="_blank"><img src="./assets/images/insta.svg" alt="instagram"></a></div>
                    		<div class="social_icon"><a href="https://www.youtube.com/@TheNESTSchoolChennai" target="_blank"><img src="./assets/images/utube.svg" alt="youtube"></a></div>
                    	</div>
                </div>
                <div class="col-12">
                     <div class="address-wrap">
        		        <p class="address">363, Arcot Road (NSK Salai), Kodambakkam, <br/>Chennai, Tamil Nadu 600 024</p>
        		        <div class="phone-numbers">
                			<p><b>For Admission Enquiries:</b><br/> <a href="tel:994-010-6378" class="contact-info">994-010-6378</a></p>
                			<p><b>For Other Enquiries:</b><br/> <a href="tel:994-010-6358" class="contact-info">994-010-6358</a></p>
                		</div>
            		</div>
                </div>
            </div>
        </div>
    </footer>
   
   <!--download files-->
    <a class="abc1" href="TNS-BrouchureNew.pdf" download style="display:none;">
        <button class="dwnload" id="d-brochure" data-modal="d-brochure">download</button>
    </a>
   
   <!--- visit-campus popup Starts --->       
    <div class="modal fade" id="visit-campus" tabindex="-1" role="dialog" aria-labelledby="visit-campusLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title main-title" id="visit-campusLongTitle">Schedule a Visit</h5>
                    
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    
                  <p  class="frm-txt">To schedule a campus visit, please fill in the information below:</p>
                  
                    <form id="form-campus" method="post" action="contact-visit-campus" autocomplete="off" class="custom-form">
                            
                            <div class="padding-wrap"> 
                            
                                <label for="name" class="form-wrap">
                                    <span class="input-label">Name</span>
                                    <input type="text" name="name" data-required="true" class="form-control" />
                                    <span class="err-txt-style">Enter your name</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Mobile Number</span>
                                    <input type="tel" name="mobile" data-required="true" maxlength="10" minlength="10" class="form-control"/>
                                    <span class="err-txt-style">Enter your mobile number</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Email</span>
                                    <input type="email" name="email" data-required="true" class="form-control"/>
                                    <span class="err-txt-style">Enter your E-mail</span>
                                </label>   
                                
                                <label for="experience" class="form-wrap">
                                  <span class="input-label">Which grade are you looking for?</span>
                                    <select name="experience" class="input-box" placeholder="Grade" data-required="true">
                                        <option value="" selected="" disabled="">Select any 1 option</option>
                                        <option value="Pre KG">Pre-KG</option>
                                        <option value="LKG">LKG</option>
                                        <option value="UKG">UKG</option>
                                        <option value="Grade 1">Grade 1</option>
                                        <option value="Grade 2">Grade 2</option>
                                        <option value="Grade 3">Grade 3</option>
                                        <option value="Grade 4">Grade 4</option> 
                                        <option value="Grade 5">Grade 5</option> 
                                        <option value="Grade 6">Grade 6</option>  
                                        <option value="Grade 7">Grade 7</option> 
                                        <option value="Grade 8">Grade 8</option> 
                                        <option value="Grade 9">Grade 9</option> 
                                        <option value="Grade 10">Grade 10</option> 
                                        <option value="Grade 11">Grade 11</option> 
                                        <option value="Grade 12">Grade 12</option> 
                                    </select>
                                    <span class="err-txt-style">Please Fill this Field</span>
                                </label>
                                
                            <input type="hidden" name="formname" data-required="false" class="form-control" value="Visit Campus"/>
                                
                                
                            <input type="hidden" name="posted_date" data-required="false" class="form-control" value="<?php echo $datevalue; ?>"/>
                            <input type="hidden" name="ip" data-required="false" class="form-control" value="<?php echo $ip; ?>"/>  
                            <input type="hidden" name="page_url" data-required="false" class="form-control" value="<?php echo $url; ?>"/>
                            <input type="hidden" name="source" data-required="false" class="form-control" value="<?php echo $source_value; ?>"/>
                            
                            
                            <label class="sub-btn" class="form-wrap">
                                <span id="loading-icon" class="loading-icon">  <img src="./assets/images/ajax-loader.gif" class="loader-img"></span>
                                <button type="submit" name="submit" class="submt-btn" id="downld-bro1">Submit</button>
                            </label>

                            </div>

                        </form>
                </div>

            </div>
        </div>
    </div>
    <!--- visit-campus popup Starts --->

    <!--- download-brochure popup Starts --->
    <div class="modal fade" id="download-brochure" tabindex="-1" role="dialog" aria-labelledby="download-brochureLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title main-title" id="download-brochureLongTitle">Download Brochure</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-style1" id="contact_form-download-brochure">

                        <div id="contact_results-download-brochure"></div>
                        
                        
                        <form id="form-download-brochure" method="post" action="contact-download-brochure" autocomplete="off" class="custom-form">
                            
                            <div class="padding-wrap"> 
                            
                                <label for="name" class="form-wrap">
                                    <span class="input-label">Name</span>
                                    <input type="text" name="name" data-required="true" class="form-control" />
                                    <span class="err-txt-style">Enter your name</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Mobile Number</span>
                                    <input type="tel" name="mobile" data-required="true" maxlength="10" minlength="10" class="form-control"/>
                                    <span class="err-txt-style">Enter your mobile number</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Email</span>
                                    <input type="email" name="email" data-required="true" class="form-control"/>
                                    <span class="err-txt-style">Enter your E-mail</span>
                                </label>
                
                                
                                <label for="experience" class="form-wrap">
                                  <span class="input-label">Which grade are you looking for?</span>
                                    <select name="experience" class="input-box" placeholder="Grade" data-required="true">
                                        <option value="" selected="" disabled="">Select any 1 option</option>
                                        <option value="Pre KG">Pre-KG</option>
                                        <option value="LKG">LKG</option>
                                        <option value="UKG">UKG</option>
                                        <option value="Grade 1">Grade 1</option>
                                        <option value="Grade 2">Grade 2</option>
                                        <option value="Grade 3">Grade 3</option>
                                        <option value="Grade 4">Grade 4</option> 
                                        <option value="Grade 5">Grade 5</option> 
                                        <option value="Grade 6">Grade 6</option>  
                                        <option value="Grade 7">Grade 7</option> 
                                        <option value="Grade 8">Grade 8</option> 
                                        <option value="Grade 9">Grade 9</option> 
                                        <option value="Grade 10">Grade 10</option> 
                                        <option value="Grade 11">Grade 11</option> 
                                        <option value="Grade 12">Grade 12</option>  
                                    </select>
                                    <span class="err-txt-style">Please Fill this Field</span>
                                </label>
                            

                                
                            <input type="hidden" name="posted_date" data-required="false" class="form-control" value="<?php echo $datevalue; ?>"/>
                            <input type="hidden" name="ip" data-required="false" class="form-control" value="<?php echo $ip; ?>"/>  
                            <input type="hidden" name="page_url" data-required="false" class="form-control" value="<?php echo $url; ?>"/>
                            <input type="hidden" name="source" data-required="false" class="form-control" value="<?php echo $source_value; ?>"/>
                            
                            <input type="hidden" name="formname" data-required="false" class="form-control" value="Download Brochure"/>
                            
                            <label class="sub-btn" class="form-wrap">
                                <span id="loading-icon" class="loading-icon">  <img src="./assets/images/ajax-loader.gif" class="loader-img"></span>
                                <button type="submit" name="submit" class="submt-btn" id="downld-bro1">Download Brochure</button>
                            </label>

                            </div>

                        </form>

                        
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--- download-brochure popup Ends --->
                    
    <!--- Register for an Open House --->
    <div class="modal fade" id="open-house" tabindex="-1" role="dialog" aria-labelledby="register-open-houseLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title main-title" id="register-open-houseLongTitle">Register for an Open House</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-style1" id="contact_form-open-house">

                     <!--   <div id="contact_results"></div> -->
                        
                        <form id="form-open-house" method="post" action="contact-open-house" autocomplete="off" class="custom-form">
                            
                            <div class="padding-wrap"> 
                                <label for="name" class="form-wrap">
                                    <span class="input-label">Name</span>
                                    <input type="text" name="name" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your name</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Mobile Number</span>
                                    <input type="tel" name="mobile" data-required="true" maxlength="10" minlength="10" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your mobile number</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Email</span>
                                    <input type="email" name="email" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your E-mail</span>
                                </label>

                                
                                <label for="experience" class="form-wrap">
                                  <span class="input-label">Which grade are you looking for?</span>
                                    <select name="experience" class="input-box" placeholder="Grade" data-required="true">
                                        <option value="" selected="" disabled="">Select any 1 option</option>
                                        <option value="Pre KG">Pre-KG</option>
                                        <option value="LKG">LKG</option>
                                        <option value="UKG">UKG</option>
                                        <option value="Grade 1">Grade 1</option>
                                        <option value="Grade 2">Grade 2</option>
                                        <option value="Grade 3">Grade 3</option>
                                        <option value="Grade 4">Grade 4</option> 
                                        <option value="Grade 5">Grade 5</option> 
                                        <option value="Grade 6">Grade 6</option>  
                                        <option value="Grade 7">Grade 7</option> 
                                        <option value="Grade 8">Grade 8</option> 
                                        <option value="Grade 9">Grade 9</option> 
                                        <option value="Grade 10">Grade 10</option> 
                                        <option value="Grade 11">Grade 11</option> 
                                        <option value="Grade 12">Grade 12</option> 
                                    </select>
                                    <span class="err-txt-style">Please Fill this Field</span>
                                </label>
                            
                                <input type="hidden" name="formname" data-required="false" class="form-control" value="Register for Open House"/>                                       
                                <input type="hidden" name="posted_date" data-required="false" class="form-control" value="<?php echo $datevalue; ?>"/>
                                <input type="hidden" name="ip" data-required="false" class="form-control" value="<?php echo $ip; ?>"/>  
                                 <input type="hidden" name="page_url" data-required="false" class="form-control" value="<?php echo $url; ?>"/>
                                <input type="hidden" name="source" data-required="false" class="form-control" value="<?php echo $source_value; ?>"/>
                        
                            </div>
                            
                            <label class="sub-btn" class="form-wrap">
                                <span id="loading-icon" class="loading-icon">  <img src="./assets/images/ajax-loader.gif" class="loader-img"></span>
                                <button type="submit" name="submit" class="submt-btn">Submit</button>
                            </label>
                                        
                        </form>
                            
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!--- Register for an enquiry-for-admission --->
    <div class="modal fade" id="enquiry-for-admission" tabindex="-1" role="dialog" aria-labelledby="register-enquiry-for-admissionLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title main-title" id="register-enquiry-for-admissionLongTitle">Enquire for Admission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-style1" id="contact_form-open-house">

                     <!--   <div id="contact_results"></div> -->
                        
                        <form id="form-enquiry-for-admission" method="post" action="contact-enquiry-for-admission" autocomplete="off" class="custom-form">
                            
                            <div class="padding-wrap"> 
                                <label for="name" class="form-wrap">
                                    <span class="input-label">Name</span>
                                    <input type="text" name="name" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your name</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Mobile Number</span>
                                    <input type="tel" name="mobile" data-required="true" maxlength="10" minlength="10" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your mobile number</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Email</span>
                                    <input type="email" name="email" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your E-mail</span>
                                </label>
                                
                                <label for="experience" class="form-wrap">
                                  <span class="input-label">Which grade are you looking for?</span>
                                    <select name="experience" class="input-box" placeholder="Grade" data-required="true">
                                        <option value="" selected="" disabled="">Select any 1 option</option>
                                        <option value="Pre KG">Pre-KG</option>
                                        <option value="LKG">LKG</option>
                                        <option value="UKG">UKG</option>
                                        <option value="Grade 1">Grade 1</option>
                                        <option value="Grade 2">Grade 2</option>
                                        <option value="Grade 3">Grade 3</option>
                                        <option value="Grade 4">Grade 4</option> 
                                        <option value="Grade 5">Grade 5</option> 
                                        <option value="Grade 6">Grade 6</option>  
                                        <option value="Grade 7">Grade 7</option> 
                                        <option value="Grade 8">Grade 8</option> 
                                        <option value="Grade 9">Grade 9</option> 
                                        <option value="Grade 10">Grade 10</option> 
                                        <option value="Grade 11">Grade 11</option> 
                                        <option value="Grade 12">Grade 12</option> 
                                    </select>
                                    <span class="err-txt-style">Please Fill this Field</span>
                                </label>
                                
                            
                                <input type="hidden" name="formname" data-required="false" class="form-control" value="Enquire for Admission"/>                                       
                                <input type="hidden" name="posted_date" data-required="false" class="form-control" value="<?php echo $datevalue; ?>"/>
                                <input type="hidden" name="ip" data-required="false" class="form-control" value="<?php echo $ip; ?>"/>  
                                <input type="hidden" name="page_url" data-required="false" class="form-control" value="<?php echo $url; ?>"/>
                                <input type="hidden" name="source" data-required="false" class="form-control" value="<?php echo $source_value; ?>"/>
                        
                            </div>
                            
                            <label class="sub-btn" class="form-wrap">
                                <span id="loading-icon" class="loading-icon">  <img src="./assets/images/ajax-loader.gif" class="loader-img"></span>
                                <button type="submit" name="submit" class="submt-btn">Submit</button>
                            </label>
                                        
                        </form>
                            
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!--- Register for an KG's admission --->
    <div class="modal fade" id="enquiry-for-admission-kgs" tabindex="-1" role="dialog" aria-labelledby="register-enquiry-for-admissionLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title main-title" id="register-enquiry-for-admissionLongTitle">Enquire for Admission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-style1" id="contact_form-kgs">
                        
                        <form id="form-enquiry-for-admission-kgs" method="post" action="contact-enquiry-for-admission" autocomplete="off" class="custom-form">
                            
                            <div class="padding-wrap"> 
                                <label for="name" class="form-wrap">
                                    <span class="input-label">Name</span>
                                    <input type="text" name="name" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your name</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Mobile Number</span>
                                    <input type="tel" name="mobile" data-required="true" maxlength="10" minlength="10" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your mobile number</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Email</span>
                                    <input type="email" name="email" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your E-mail</span>
                                </label>
                                
                                <label for="experience" class="form-wrap">
                                  <span class="input-label">Which grade are you looking for?</span>
                                    <select name="experience" class="input-box" placeholder="Grade" data-required="true">
                                        <option value="" selected="" disabled="">Select any 1 option</option>
                                        <option value="Pre KG">Pre-KG</option>
                                        <option value="LKG">LKG</option>
                                        <option value="UKG">UKG</option>
                                    </select>
                                    <span class="err-txt-style">Please Fill this Field</span>
                                </label>
                                
                            
                                <input type="hidden" name="formname" data-required="false" class="form-control" value="Enquire for KGs Admission"/>                                       
                                <input type="hidden" name="posted_date" data-required="false" class="form-control" value="<?php echo $datevalue; ?>"/>
                                <input type="hidden" name="ip" data-required="false" class="form-control" value="<?php echo $ip; ?>"/>  
                                <input type="hidden" name="page_url" data-required="false" class="form-control" value="<?php echo $url; ?>"/>
                                <input type="hidden" name="source" data-required="false" class="form-control" value="<?php echo $source_value; ?>"/>
                        
                            </div>
                            
                            <label class="sub-btn" class="form-wrap">
                                <span id="loading-icon" class="loading-icon">  <img src="./assets/images/ajax-loader.gif" class="loader-img"></span>
                                <button type="submit" name="submit" class="submt-btn">Submit</button>
                            </label>
                                        
                        </form>
                            
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!--- Register for an Grade 1 to 5 admission --->
    <div class="modal fade" id="enquiry-for-admission-grade1to5" tabindex="-1" role="dialog" aria-labelledby="register-enquiry-for-admissionLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title main-title" id="register-enquiry-for-admissionLongTitle">Enquire for Admission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-style1" id="contact_form-grade1to5">
                        
                        <form id="form-enquiry-for-admission-grade1to5" method="post" action="contact-enquiry-for-admission" autocomplete="off" class="custom-form">
                            
                            <div class="padding-wrap"> 
                                <label for="name" class="form-wrap">
                                    <span class="input-label">Name</span>
                                    <input type="text" name="name" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your name</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Mobile Number</span>
                                    <input type="tel" name="mobile" data-required="true" maxlength="10" minlength="10" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your mobile number</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Email</span>
                                    <input type="email" name="email" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your E-mail</span>
                                </label>
                                
                                <label for="experience" class="form-wrap">
                                  <span class="input-label">Which grade are you looking for?</span>
                                    <select name="experience" class="input-box" placeholder="Grade" data-required="true">
                                        <option value="" selected="" disabled="">Select any 1 option</option>
                                        <option value="Grade 1">Grade 1</option>
                                        <option value="Grade 2">Grade 2</option>
                                        <option value="Grade 3">Grade 3</option>
                                        <option value="Grade 4">Grade 4</option> 
                                        <option value="Grade 5">Grade 5</option> 
                                    </select>
                                    <span class="err-txt-style">Please Fill this Field</span>
                                </label>
                                
                            
                                <input type="hidden" name="formname" data-required="false" class="form-control" value="Enquire for Grade 1 to 5 Admission"/>                                       
                                <input type="hidden" name="posted_date" data-required="false" class="form-control" value="<?php echo $datevalue; ?>"/>
                                <input type="hidden" name="ip" data-required="false" class="form-control" value="<?php echo $ip; ?>"/>  
                                <input type="hidden" name="page_url" data-required="false" class="form-control" value="<?php echo $url; ?>"/>
                                <input type="hidden" name="source" data-required="false" class="form-control" value="<?php echo $source_value; ?>"/>
                        
                            </div>
                            
                            <label class="sub-btn" class="form-wrap">
                                <span id="loading-icon" class="loading-icon">  <img src="./assets/images/ajax-loader.gif" class="loader-img"></span>
                                <button type="submit" name="submit" class="submt-btn">Submit</button>
                            </label>
                                        
                        </form>
                            
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!--- Register for an Grade 6 to 8 admission --->
    <div class="modal fade" id="enquiry-for-admission-grade6to8" tabindex="-1" role="dialog" aria-labelledby="register-enquiry-for-admissionLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title main-title" id="register-enquiry-for-admissionLongTitle">Enquire for Admission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-style1" id="contact_form-grade6to8">
                        
                        <form id="form-enquiry-for-admission-grade6to8" method="post" action="contact-enquiry-for-admission" autocomplete="off" class="custom-form">
                            
                            <div class="padding-wrap"> 
                                <label for="name" class="form-wrap">
                                    <span class="input-label">Name</span>
                                    <input type="text" name="name" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your name</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Mobile Number</span>
                                    <input type="tel" name="mobile" data-required="true" maxlength="10" minlength="10" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your mobile number</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Email</span>
                                    <input type="email" name="email" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your E-mail</span>
                                </label>
                                
                                <label for="experience" class="form-wrap">
                                  <span class="input-label">Which grade are you looking for?</span>
                                    <select name="experience" class="input-box" placeholder="Grade" data-required="true">
                                        <option value="" selected="" disabled="">Select any 1 option</option>
                                        <option value="Grade 6">Grade 6</option>  
                                        <option value="Grade 7">Grade 7</option> 
                                        <option value="Grade 8">Grade 8</option> 
                                    </select>
                                    <span class="err-txt-style">Please Fill this Field</span>
                                </label>
                                
                            
                                <input type="hidden" name="formname" data-required="false" class="form-control" value="Enquire for Grade 6 to 8 Admission"/>                                       
                                <input type="hidden" name="posted_date" data-required="false" class="form-control" value="<?php echo $datevalue; ?>"/>
                                <input type="hidden" name="ip" data-required="false" class="form-control" value="<?php echo $ip; ?>"/>  
                                <input type="hidden" name="page_url" data-required="false" class="form-control" value="<?php echo $url; ?>"/>
                                <input type="hidden" name="source" data-required="false" class="form-control" value="<?php echo $source_value; ?>"/>
                        
                            </div>
                            
                            <label class="sub-btn" class="form-wrap">
                                <span id="loading-icon" class="loading-icon">  <img src="./assets/images/ajax-loader.gif" class="loader-img"></span>
                                <button type="submit" name="submit" class="submt-btn">Submit</button>
                            </label>
                                        
                        </form>
                            
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!--- Register for an Grade 9-10 admission --->
    <div class="modal fade" id="enquiry-for-admission-grade9-10" tabindex="-1" role="dialog" aria-labelledby="register-enquiry-for-admissionLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title main-title" id="register-enquiry-for-admissionLongTitle">Enquire for Admission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-style1" id="contact_form-grade9-10">
                        
                        <form id="form-enquiry-for-admission-grade9-10" method="post" action="contact-enquiry-for-admission" autocomplete="off" class="custom-form">
                            
                            <div class="padding-wrap"> 
                                <label for="name" class="form-wrap">
                                    <span class="input-label">Name</span>
                                    <input type="text" name="name" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your name</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Mobile Number</span>
                                    <input type="tel" name="mobile" data-required="true" maxlength="10" minlength="10" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your mobile number</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Email</span>
                                    <input type="email" name="email" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your E-mail</span>
                                </label>
                                
                                <label for="experience" class="form-wrap">
                                  <span class="input-label">Which grade are you looking for?</span>
                                    <select name="experience" class="input-box" placeholder="Grade" data-required="true">
                                        <option value="" selected="" disabled="">Select any 1 option</option>
                                        <option value="Grade 9">Grade 9</option> 
                                        <option value="Grade 10">Grade 10</option> 
                                    </select>
                                    <span class="err-txt-style">Please Fill this Field</span>
                                </label>
                                
                            
                                <input type="hidden" name="formname" data-required="false" class="form-control" value="Enquire for Grade 9-10 Admission"/>                                       
                                <input type="hidden" name="posted_date" data-required="false" class="form-control" value="<?php echo $datevalue; ?>"/>
                                <input type="hidden" name="ip" data-required="false" class="form-control" value="<?php echo $ip; ?>"/>  
                                <input type="hidden" name="page_url" data-required="false" class="form-control" value="<?php echo $url; ?>"/>
                                <input type="hidden" name="source" data-required="false" class="form-control" value="<?php echo $source_value; ?>"/>
                        
                            </div>
                            
                            <label class="sub-btn" class="form-wrap">
                                <span id="loading-icon" class="loading-icon">  <img src="./assets/images/ajax-loader.gif" class="loader-img"></span>
                                <button type="submit" name="submit" class="submt-btn">Submit</button>
                            </label>
                                        
                        </form>
                            
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!--- Register for an Grade 11-12 admission --->
    <div class="modal fade" id="enquiry-for-admission-grade11-12" tabindex="-1" role="dialog" aria-labelledby="register-enquiry-for-admissionLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title main-title" id="register-enquiry-for-admissionLongTitle">Enquire for Admission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-style1" id="contact_form-grade11-12">
                        
                        <form id="form-enquiry-for-admission-grade11-12" method="post" action="contact-enquiry-for-admission" autocomplete="off" class="custom-form">
                            
                            <div class="padding-wrap"> 
                                <label for="name" class="form-wrap">
                                    <span class="input-label">Name</span>
                                    <input type="text" name="name" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your name</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Mobile Number</span>
                                    <input type="tel" name="mobile" data-required="true" maxlength="10" minlength="10" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your mobile number</span>
                                </label>
                                
                                <label for="email" class="form-wrap">
                                    <span class="input-label">Email</span>
                                    <input type="email" name="email" data-required="true" placeholder="" class="form-control"/>
                                    <span class="err-txt-style">Enter your E-mail</span>
                                </label>
                                
                                <label for="experience" class="form-wrap">
                                  <span class="input-label">Which grade are you looking for?</span>
                                    <select name="experience" class="input-box" placeholder="Grade" data-required="true">
                                        <option value="" selected="" disabled="">Select any 1 option</option>
                                        <option value="Grade 11">Grade 11</option> 
                                        <option value="Grade 12">Grade 12</option> 
                                    </select>
                                    <span class="err-txt-style">Please Fill this Field</span>
                                </label>
                                
                            
                                <input type="hidden" name="formname" data-required="false" class="form-control" value="Enquire for Grade 11-12 Admission"/>                                       
                                <input type="hidden" name="posted_date" data-required="false" class="form-control" value="<?php echo $datevalue; ?>"/>
                                <input type="hidden" name="ip" data-required="false" class="form-control" value="<?php echo $ip; ?>"/>  
                                <input type="hidden" name="page_url" data-required="false" class="form-control" value="<?php echo $url; ?>"/>
                                <input type="hidden" name="source" data-required="false" class="form-control" value="<?php echo $source_value; ?>"/>
                        
                            </div>
                            
                            <label class="sub-btn" class="form-wrap">
                                <span id="loading-icon" class="loading-icon">  <img src="./assets/images/ajax-loader.gif" class="loader-img"></span>
                                <button type="submit" name="submit" class="submt-btn">Submit</button>
                            </label>
                                        
                        </form>
                            
                    </div>
                </div>
            </div>
        </div>
    </div>
        
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
    <!-- Ajax contact form  -->
    <script type="text/javascript" src="assets/js/app.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    
     <!-- Image Lightbox js -->
    <script src="./assets/js/simple-lightbox.min.js"></script>
    <script src="./assets/js/simple-lightbox.legacy.min.js"></script>
    <script src="./assets/js/simple-lightbox.jquery.min.js"></script>
 
    <script>
    $(document).ready(function() {
        
        $('#event-slider').owlCarousel({
            loop: false,
            margin: 10,
            autoplay: false,
            responsiveClass: true,
            nav: false,
            onInitialized: counter,
            onTranslated: counter,
            navText: ["<i class='fa fa-chevron-left thickness-reduce'></i>", "<i class='fa fa-chevron-right thickness-reduce'></i>"],
            responsive: {
                0: {
                    items: 1,
                },
                579: {
                    items: 1,
                },
                1000: {
                    items: 1,
                    slideBy: 1
                }
            }
        })

        $('.carousel-control.left').click(function () {
            $('#carouselgallery').carousel('prev');
        });

        $('.carousel-control.right').click(function () {
            $('#carouselgallery').carousel('next');
        });


        function counter(event) {
            var element = event.target;   
            var items = event.item.count;
            var item = event.item.index + 1;

            // if loop is true then reset counter from 1
            if (item > items) {
                item = item - items
            }
            $('#counter').html(+item + " / " + items)
        }

        $('.gallery a').simpleLightbox({
            sourceAttr:'data-url',
            showCounter:true,
            animationSpeed: 50,
         });

    });
    </script>
    
</body>
</html>