<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<h1><b style="color: #f00;"><?php echo $severityLevel; ?></b> A PHP Error was encountered</h1>

<div>
    <b style="font-size: 16px">Message:</b>
    <span style="font-size: 14px"><?php echo $message; ?></span>
</div>
<div>
    <b style="font-size: 16px">File:</b>
    <span style="font-size: 14px"><?php echo $file; ?></span>
</div>
<div>
    <b style="font-size: 16px">Line:</b>
    <span style="font-size: 14px"><?php echo $line; ?></span>
</div>

<?php if (function_exists('debug_backtrace') and $context = debug_backtrace()): ?>
    <div>
        <b style="font-size: 16px">Backtrace:</b>
    </div>
    <ul>
        <?php foreach ($context as $c): ?>
            <li>
                <p>
                <div>
                    <b style="font-size: 14px">File:</b>
                    <span style="font-size: 12px"><?php echo $c['file'] ?? ''; ?></span>
                </div>
                <div>
                    <b style="font-size: 14px">Line:</b>
                    <span style="font-size: 12px"><?php echo $c['line'] ?? ''; ?></span>
                </div>
                <div>
                    <b style="font-size: 14px">Class:</b>
                    <span style="font-size: 12px"><?php echo $c['class'] ?? ''; ?></span>
                </div>
                <div>
                    <b style="font-size: 14px">Function:</b>
                    <span style="font-size: 12px"><?php echo $c['function'] ?? ''; ?></span>
                </div>
                </p>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
</body>
</html>