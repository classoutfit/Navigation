<?php if ($this->Paginator->hasPrev() || $this->Paginator->hasNext()) {
	/*
$this->Paginator->options(array(
		'update' => '#content',
		'evalScripts' => true,
		'before' => $this->Js->get('li.busy')->effect('fadeIn', array('buffer' => false)),
		'complete' => $this->Js->get('li.busy')->effect('fadeOut', array('buffer' => false))
	));
*/ ?>

	<ul class="pagination">
		<?php
			echo $this->Paginator->first(
				'<i class="icon-fast-backward"></i>',
				array(
					'escape' => false,
					'tag' => 'li'
				),
				'<a onclick="return false;"><i class="icon-fast-backward"></i></a>',
				array(
					'class' => 'disabled',
					'escape' => false,
					'tag' => 'li'
				)

			);

			echo $this->Paginator->prev(
				'<i class="icon-step-backward"></i>',
				array(
					'escape' => false,
					'tag' => 'li',
				),
				'<a onclick="return false;"><i class="icon-step-backward"></i></a>',
				array(
					'class' => 'disabled',
					'escape' => false,
					'tag' => 'li'
				)
			);

			echo $this->Paginator->numbers(
				array(
					'currentClass' => 'active',
					'currentTag' => 'span',
					'tag' => 'li',
					'separator' => '',
					'modulus' => 7
				)
			);

			echo $this->Paginator->next(
				'<i class="icon-step-forward"></i>',
				array(
					'escape' => false,
					'tag' => 'li'
				),
				'<a onclick="return false;"><i class="icon-step-forward"></i></a>',
				array(
					'class' => 'disabled',
					'escape' => false,
					'tag' => 'li'
				)
			);
			echo $this->Paginator->last(
				'<i class="icon-fast-forward"></i>',
				array(
					'escape' => false,
					'tag' => 'li'
				),
				'<a onclick="return false;"><i class="icon-fast-forward"></i></a>',
				array(
					'class' => 'disabled',
					'escape' => false,
					'tag' => 'li'
				)
			);
		?>
	</ul>

	<?php echo $this->Js->writeBuffer();
} ?>