<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>IT Handout Envelope</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; }
        .envelope { border: 2px solid #000; padding: 40px; width: 600px; margin: 40px auto; }
        .qr { text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="envelope">
        <h2>پاکت رمز عبور موقت - دانشگاه</h2>
        <p><strong>شماره دانشجویی:</strong> <?php echo e($user->id); ?></p>
        <p><strong>نام:</strong> <?php echo e($user->first_name); ?> <?php echo e($user->last_name); ?></p>
        <p><strong>رمز موقت:</strong> <code><?php echo e($tempPassword); ?></code></p>
        <p><strong>تاریخ انقضا:</strong> <?php echo e($user->temporary_password_expires_at->format('Y/m/d')); ?></p>
        
        <div class="qr">
            <?php echo $qr; ?>

        </div>
        
        <p style="font-size: 12px; color: #666;">این رمز فقط یکبار قابل استفاده است. پس از ورود، رمز خود را تغییر دهید.</p>
    </div>
</body>
</html><?php /**PATH /home/user/Unify-v3/unify-backend/resources/views/envelopes/it-handout.blade.php ENDPATH**/ ?>