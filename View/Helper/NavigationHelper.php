<?php
class NavigationHelper extends AppHelper {

    var $helpers = ['Html', 'Form', 'Session'];

    public function menu($menuName = null, $menuOptions = [])
    {
        if (! $menuName) {
            return '';
        }

        Configure::load('menus');

        $this->menu = Configure::read('menus.' . $menuName);

        if (! $this->menu || empty($this->menu)) {
            return '';
        }

        $this->params = isset($menuOptons['params'])
            ? $menuOptons['params']
            : $this->params
        ;

        $this->menuOptions = $menuOptions;

        $this->setActiveTab();

        $output = '';

        $roleId = $this->Session->check('Auth.User.role_id')
            ? $this->Session->read('Auth.User.role_id')
            : ''
        ;

        foreach ($this->menu as $menuItemName => $menuItem) {

            if (! isset($menuItem['Auth'][$roleId]) || $menuItem['Auth'][$roleId] != 'deny') {
                if (isset($this->menuOptions['prefix'])) {
                    $menuItem['text'] = $this->menuOptions['prefix'].$menuItem['text'];
                }
                if (isset($this->menuOptions['data'])) {
                    foreach ($this->menuOptions['data'] as $field => $value) {
                        $menuItem['text'] = str_replace('{'.$field.'}', $value, $menuItem['text']);
                    }
                }
                if (isset($this->menuOptions['tag'])) {
                    if (isset($menuItem['dropdown'])) {
                        if (isset($menuItem['tag_options']['class'])) {
                            $menuItem['tag_options']['class'] .= ' dropdown';
                        } else {
                            $menuItem['tag_options']['class'] = 'dropdown';
                        }

                        $output .= $this->Html->tag(
                            $this->menuOptions['tag'],
                            $this->dropdown(
                                $menuItem['text'],
                                $menuItem['dropdown']
                            ),
                            $menuItem['tag_options']
                        );

                    } else {
                        $output .= $this->Html->tag(
                            $this->menuOptions['tag'],
                            $this->Html->link(
                                $menuItem['text'],
                                $menuItem['url'],
                                isset($menuItem['options']) ? $menuItem['options'] : []
                            ),
                            isset($menuItem['tag_options']) ? $menuItem['tag_options'] : []
                        );
                    }
                } else {
                    $output .= $this->Html->link(
                        $menuItem['text'],
                        $menuItem['url'],
                        isset($menuItem['options']) ? $menuItem['options'] : []
                    );
                }

                if (isset($menuItem['divide'])) {
                    $output .= $this->divider();
                }
            }
        }

        return $output;
    }

    private function setActiveTab()
    {
        $active = isset($this->menuOptions['active'])
            ? $this->menuOptions['active']
            : null
        ;

        if ($active && $this->activeTabExists($active)) {
            return true;
        }

        $activeTabs = [];

        if (! empty($this->menuOptions['prefix'])) {
            if (! empty($this->menuOptions['id'])) {
                $activeTabs[] = $this->menuOptions['prefix'] . '/'. $this->params['controller'] . '/' . $this->params['action'] . '/' . $this->menuOptions['id'];
            }
            $activeTabs = array_merge(
                $activeTabs,
                [
                    $this->menuOptions['prefix'].'/'.$this->params['controller'].'/'.$this->params['action'],
                    $this->menuOptions['prefix'].'/'.$this->params['controller'],
                    $this->menuOptions['prefix'].'/'.$this->params['action']
                ]
            );
        }

        if (! empty($this->menuOptions['id'])) {
            $activeTabs[] = $this->params['controller'].'/'.$this->params['action'].'/'.$this->params['pass'][0];
        }

        $activeTabs = array_merge(
            $activeTabs,
            [
                $this->params['controller'].'/'.$this->params['action'],
                $this->params['controller'],
                $this->params['action']
            ]
        );

        $found = false;
        for ($i = 0, $len = count($activeTabs); $i < $len && !$found; $i++) {
            $active = $activeTabs[$i];
            $found = $this->activeTabExists($active);
        }

    }

    private function activeTabExists($active)
    {
        if (isset($this->menu[$active])) {
            $this->menu[$active]['tag_options'] = ['class' => 'active'];
            return true;
        }
        return false;
    }

    public function quickLinks($menuName = null) {
        if (! $menuName) return null;

        return $this->Html->tag(
            'ul',
            $this->menu(
                $menuName,
                ['tag' => 'li']
            ),
            ['class' => 'masthead-links']
        );
    }

    private function includeLink($linkName = null, $linkOptions = [])
    {
        if (isset($linkOptions['blackList'][$linkName])) {
            return false;
        } elseif (isset($linkOptions['whiteList'][$linkName])) {
            return true;
        } elseif (isset($linkOptions['greyList'][$linkName]) && !isset($linkOptions['whiteList'][$linkName])) {
            return false;
        } elseif (! empty($linkOptions['whiteList']) && !isset($linkOptions['whiteList'][$linkName])) {
            return false;
        }

        return true;
    }

    public function actions($menuOptions = [])
    {
        $defaults = [
            'id' => null,
            'title' => null,
            'name'  => null,
            'blackList' => [],
            'whiteList' => [],
            'prefix' => null,
            'controller' => $this->params['controller'],
            'style' => 'form-actions'
        ];

        $menuOptions = array_merge(
            $defaults,
            $menuOptions
        );

        if (in_array($menuOptions['style'], ['form-actions', 'buttonbar'])) {
            $menuOptions['linkClass'] = 'btn';
        } elseif ($menuOptions['style'] == 'icon') {
            $menuOptions['linkClass'] = 'btn-icon';
        } else {
            $menuOptions['linkClass'] = 'index-link';
        }

        // if the white list is populated only use these items
        if (! empty($menuOptions['whiteList'])) {
            $menuOptions['whiteList'] = array_flip($menuOptions['whiteList']);
        }

        // if something is in the black list, don't use it
        if (! empty($menuOptions['blackList'])) {
            $menuOptions['blackList'] = array_flip($menuOptions['blackList']);
        }

        // something in the grey list is only added if it is in the white list
        $menuOptions['greyList'] = [
            'save' => 0,
            'view' => 0,
            'cancel_add' => 0,
            'cancel_edit' => 0,
            'cancel_add_ajax' => 0,
            'modal_cancel' => 0
        ];

        $this->menuOptions = $menuOptions;
        $output = '';
        $this->navActions = [];

        $this->addActionButton(
            'save',
            'submit',
            'fa fa-save',
            'Save',
            $class = 'btn btn-success'
        );
        $this->addActionLink([
            'action' => 'modal_cancel',
            'targetAction' => 'index',
            'icon' => 'fa fa-undo',
            'text' => 'Cancel'
        ]);
        $this->addActionLink([
            'action' => 'cancel_add',
            'targetAction' => 'index',
            'icon' => 'fa fa-undo',
            'text' => 'Cancel'
        ]);
        $this->addActionLink([
            'action' => 'cancel_edit',
            'targetAction' => 'view',
            'id' => $this->menuOptions['id'],
            'icon' => 'fa fa-undo',
            'text' => 'Cancel'
        ]);
        $this->addActionLink([
            'action' => 'view',
            'targetAction' => 'view',
            'id' => $this->menuOptions['id'],
            'icon' => 'fa fa-zoom',
            'text' => 'View'
            ]);
        $this->addActionLink([
            'action' => 'edit',
            'targetAction' => 'edit',
            'id' => $this->menuOptions['id'],
            'icon' => 'fa fa-edit',
            'text' => 'Edit'
            ]);
        $this->addActionLink([
            'action' => 'delete',
            'targetAction' => 'delete',
            'id' => $this->menuOptions['id'],
            'icon' => 'fa fa-trash',
            'text' => 'Delete',
            'confirmText' => 'Are you sure you want to delete ' . $this->menuOptions['title'] . '?',
            'postLink' => true
        ]);
        $this->addActionLink([
            'action' => 'index',
            'targetAction' => 'index',
            'id' => $this->menuOptions['id'],
            'icon' => 'fa fa-list',
            'text' => 'List all'
        ]);
        $this->addActionLink([
            'action' => 'add',
            'targetAction' => 'add',
            'id' => $this->menuOptions['id'],
            'icon' => 'fa fa-plus-square',
            'text' => 'Add a new ' . $this->menuOptions['name']
        ]);

        $output = '';

        if ($this->navActions) {

            switch ($this->menuOptions['style']) {
                case 'form-actions':
                    $output = '<div class="form-actions"><div class="btn-group">';
                        foreach ($this->navActions as $action) {
                            $output .= $action;
                        }
                    $output .= '</div></div>';
                    break;
                case 'buttonbar':
                    $output = '<div class="btn-group">';
                        foreach ($this->navActions as $action) {
                            $output .= $action;
                        }
                    $output .= '</div>';
                    break;
                case 'pills':
                    $output = '<ul class="nav nav-pills nav-pills-actions">';
                        foreach ($this->navActions as $action) {
                            $output .= '<li>' . $action . '</li>';
                        }
                    $output .= '</ul>';
                    break;
                case 'links';
                case 'icon':
                    foreach ($this->navActions as $action) {
                        $output .= $action;
                    }
                    break;
            }

        }

        return $output;
    }

    /**
     * Adds a link to the $actions array
     * @param array $options Options that define the link
     * -- action                The action of the link
     * -- targetAction          The target action of the link
     * -- id = null             An id to be included in a link
     * -- icon                  An icon to be displayed in the link
     * -- text                  The text to be displayed on the link
     * -- promptText = null     Any prompt text for confirming the action of the link
     * -- postLink = false      Determines is this is a postLink or not
     */
    private function addActionLink($options = [])
    {
        $defaults = [
            'id' => null,
            'promptText' => null,
            'postLink' => false,
            'linkClass' => 'btn btn-default'
        ];

        $options = array_merge($defaults, $options);

        extract($options);

        if (! $this->includeLink($action, $this->menuOptions)) {
            return;
        }

        $url = [
            'controller' => $this->menuOptions['controller'],
            'action' => $targetAction,
            $id
        ];

        if ($this->menuOptions['prefix']) {
            $url[$this->menuOptions['prefix']] = true;
        }

        if ($postLink) {
            $this->navActions[] = $this->Form->postLink(
                '<i class="' . $icon . '"></i> ' . $text,
                $url,
                [
                    'escape' => false,
                    'class' => $linkClass
                ],
                $promptText
            );
        } else {
            if ($action == 'modal_cancel') {
                $this->navActions[] = $this->Form->button(
                    'Cancel',
                    [
                        'type' => 'button',
                        'class' => 'btn btn-default',
                        'data-dismiss' => 'modal'
                    ]
                );
            } else {
                $this->navActions[] = $this->Html->link(
                    '<i class="' . $icon . '"></i> ' . $text,
                    $url,
                    [
                        'escape' => false,
                        'class' => $linkClass
                    ],
                    $promptText
                );
            }

        }
    }

    private function addActionButton($action, $type = 'submit', $icon, $text, $class = 'btn')
    {
        if ($this->includeLink($action, $this->menuOptions)) {
            $this->navActions[] = $this->Form->button(
                '<i class="' . $icon . '"></i> ' . $text,
                [
                    'type' => $type,
                    'escape' => false,
                    'class' => $class
                ]
            );
        }
    }

    public function user_entry()
    {
        if ($this->Session->check('Auth.User')) {
            return $this->user_entry_logged_in();
        } else {
            return $this->user_entry_logged_out();
        }

    }

    public function user_entry_logged_out()
    {
        return $this->Html->tag(
            'ul',
            $this->menu(
                'user_entry_logged_out',
                ['tag' => 'li']
            ),
            ['class' => 'nav pull-right']
        );
    }

    public function user_entry_logged_in()
    {
        return $this->Html->tag(
            'ul',
            $this->menu(
                'user_entry_logged_in',
                ['tag' => 'li']
            ),
            ['class' => 'nav pull-right']
        );

    }

    private function dropdown($text, $dropdown)
    {
        $output = '<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . $text . ' <b class="caret"></b></a>';
        $output .= '<ul class="dropdown-menu" role="menu">';
        $output .= $this->menu($dropdown, ['tag' => 'li']);
        $output .= '</ul>';

        return $output;
    }

    private function divider()
    {
        return '<li class="divider"></li>';
    }

}