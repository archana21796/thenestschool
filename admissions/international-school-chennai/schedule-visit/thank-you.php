<!doctype html>
<html>
<head>
<!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0DS3JJFZJ1"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
    
      gtag('config', 'G-0DS3JJFZJ1');
    </script>
    
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-P4DTHT74');</script>
    <!-- End Google Tag Manager -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Thank you">

    <title>The NEST School</title>
     <!-- Bootstrap CSS -->
     <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <link href="form-style.css" rel="stylesheet">
    
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
    fbq('init', '858972470317641');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=858972470317641&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->

</head>

<body>
     <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-P4DTHT74"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    
    <div class="container">
      <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success" style="margin-top: 10%;margin-left: 1%;margin-right: 1%;text-align: center;"> 
                <strong>Success!</strong> Thank you for contacting us. Our team will connect with you soon.
                <div id="download-toast" style="
    position: fixed;
    display: none;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #000;
    color: #fff;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 14px;
    z-index: 9999;">
    Your brochure is downloading…
    </div>
            </div>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-12">
            <div style="margin-top: 10%;margin-left: 1%;margin-right: 1%;text-align: center;"> 
             <p style="font-size: 14px;"> You will be redirected to the home page in a few seconds.</p>
            </div>
        </div>
      </div>
    </div>
    
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
<script type="text/javascript">
    $(document).ready(function() {
		setTimeout(function(){
		   const params = new URLSearchParams(window.location.search);
            const encoded = params.get("from");
            
            if(encoded){
                const previousURL = atob(encoded); // Decode
                window.location.replace('https://thenest.school/');
            }

		}, 7000);
    });
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const formType = urlParams.get('form');

    if (formType === "contact-download-brochure") {

        const link = document.getElementById("download-toast");
        link.style.display = "block";
    }
});
</script>

</body>
</html>
