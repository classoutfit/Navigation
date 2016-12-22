<?php if ($this->Paginator->hasPrev() || $this->Paginator->hasNext()) { ?>

    <ul class="pagination">
        <?php
            echo $this->Paginator->first(
                '<i class="fa fa-fast-backward"></i>',
                [
                    'escape' => false,
                    'tag' => 'li'
                ],
                '<a onclick="return false;"><i class="fa fa-fast-backward"></i></a>',
                [
                    'class' => 'disabled',
                    'escape' => false,
                    'tag' => 'li'
                ]
            );

            echo $this->Paginator->prev(
                '<i class="fa fa-step-backward"></i>',
                [
                    'escape' => false,
                    'tag' => 'li',
                ],
                '<a onclick="return false;"><i class="fa fa-step-backward"></i></a>',
                [
                    'class' => 'disabled',
                    'escape' => false,
                    'tag' => 'li'
                ]
            );

            echo $this->Paginator->numbers(
                [
                    'currentClass' => 'active',
                    'currentTag' => 'span',
                    'tag' => 'li',
                    'separator' => '',
                    'modulus' => 7
                ]
            );

            echo $this->Paginator->next(
                '<i class="fa fa-step-forward"></i>',
                [
                    'escape' => false,
                    'tag' => 'li'
                ],
                '<a onclick="return false;"><i class="fa fa-step-forward"></i></a>',
                [
                    'class' => 'disabled',
                    'escape' => false,
                    'tag' => 'li'
                ]
            );
            echo $this->Paginator->last(
                '<i class="fa fa-fast-forward"></i>',
                [
                    'escape' => false,
                    'tag' => 'li'
                ],
                '<a onclick="return false;"><i class="fa fa-fast-forward"></i></a>',
                [
                    'class' => 'disabled',
                    'escape' => false,
                    'tag' => 'li'
                ]
            );
        ?>
    </ul>

    <?php echo $this->Js->writeBuffer();

} ?>