<nav id="main-navbar" class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#main-navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <?php echo $this->Html->link(
                'BuzzPaid',
                '/',
                ['class' => 'navbar-brand']
            );?>
        </div>

        <div class="collapse navbar-collapse" id="main-navbar-collapse">
            <ul class="nav navbar-nav">
                <?php echo $this->Navigation->menu(
                    isset($menuName) ? $menuName : 'main',
                    [
                        'tag' => 'li',
                        'active' => 'home'
                    ]
                ); ?>
            </ul>

            <ul class="nav navbar-nav navbar-right">
                <?php
                    if ($this->Session->check('Auth.User.id')) {
                        echo $this->Navigation->menu(
                            'user_logged_in',
                            array(
                                'tag' => 'li',
                                'data' => ['username' => $this->Session->read('Auth.User.full_name')]
                            )
                        );
                    } else {
                        echo $this->Navigation->menu(
                            'user_logged_out',
                            ['tag' => 'li']
                        );
                    }
                ?>
            </ul>

        </div>
    </div>
</nav>