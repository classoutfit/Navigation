<div class="navbar">
    <div class="navbar-inner">
        <ul class="nav">
            <?php
                echo $this->Navigation->menu(
                    $menuName,
                    [
                        'tag' => 'li',
                        'active' => $active
                    ]
                );
            ?>
        </ul>
    </div>
</div>