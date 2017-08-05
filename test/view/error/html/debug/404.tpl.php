<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<h1><b style="color: #f00;"><?php echo $status; ?></b> An uncaught Exception was encountered</h1>
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
<div>
    <b style="font-size: 16px">Request uri:</b>
    <span style="font-size: 14px"><?php echo $router->getRequestUri(); ?></span>
</div>
<div>
    <b style="font-size: 16px">PathInfo uri:</b>
    <span style="font-size: 14px"><?php echo $router->getPathInfoUri(); ?></span>
</div>

<?php if ($context): ?>
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