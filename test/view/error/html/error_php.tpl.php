<?php
defined('APP_ENTRY') or exit('Access Denied');
?>

<div style="border:1px solid #dd4814;padding-left:20px;margin:10px 0;">
	
	<h4>A PHP Error was encountered</h4>
	
	<p>Severity: <?php echo $this->severity; ?></p>
	<p>Message: <?php echo $this->message; ?></p>
	<p>Filename: <?php echo $this->filepath; ?></p>
	<p>Line Number: <?php echo $this->line; ?></p>
	
	<?php if (defined('SHOW_DEBUG_BACKTRACE') and SHOW_DEBUG_BACKTRACE === true): ?>
		
		<p>Backtrace:</p>
		<?php foreach (debug_backtrace() as $error): ?>
			
			<?php if (isset($error['file']) and strpos($error['file'], realpath(BASEPATH)) !== 0): ?>
				
				<p style="margin-left:10px">
					File: <?php echo $error['file'] ?><br/>
					Line: <?php echo $error['line'] ?><br/>
					Function: <?php echo $error['function'] ?>
				</p>
			
			<?php endif ?>
		
		<?php endforeach ?>
	
	<?php endif ?>

</div>
