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

        $params = isset($menuOptons['params'])
            ? $menuOptons['params']
            : $this->request->params
        ;

        $this->setActiveTab($menuOptions);

        $output = '';

        $roleId = $this->Session->check('Auth.User.role_id')
            ? $this->Session->read('Auth.User.role_id')
            : ''
        ;

        foreach ($this->menu as $menuItemName => $menuItem) {

            if (! isset($menuItem['Auth'][$roleId]) || $menuItem['Auth'][$roleId] != 'deny') {
                if (isset($menuOptions['prefix'])) {
                    $menuItem['text'] = $menuOptions['prefix'].$menuItem['text'];
                }
                if (isset($menuOptions['data'])) {
                    foreach ($menuOptions['data'] as $field => $value) {
                        $menuItem['text'] = str_replace('{'.$field.'}', $value, $menuItem['text']);
                    }
                }
                if (isset($menuOptions['tag'])) {
                    if (isset($menuItem['dropdown'])) {
                        if (isset($menuItem['tag_options']['class'])) {
                            $menuItem['tag_options']['class'] .= ' dropdown';
                        } else {
                            $menuItem['tag_options']['class'] = 'dropdown';
                        }

                        $output .= $this->Html->tag(
                            $menuOptions['tag'],
                            $this->dropdown(
                                $menuItem['text'],
                                $menuItem['dropdown'],
                                $params
                            ),
                            $menuItem['tag_options']
                        );

                    } else {
                        $output .= $this->Html->tag(
                            $menuOptions['tag'],
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

    private function setActiveTab($menuOptions = [])
    {
        $active = isset($menuOptions['active'])
            ? $menuOptions['active']
            : null
        ;

        if ($active && $this->activeTabExists($active)) {
            return true;
        }

        $prefix = isset($this->request->params['prefix']) && $this->request->params['prefix']
            ? $this->request->params['prefix']
            : null
        ;

        $param = isset($this->request->params['pass'][0])
            ? $this->request->params['pass'][0]
            : null
        ;

        $activeTabs = [];

        if ($prefix) {
            if ($param) {
                $activeTabs[] = $prefix.'/'.$this->request->params['controller'].'/'.$this->request->params['action'].'/'.$param;
            }
            $activeTabs = array_merge(
                $activeTabs,
                [
                    $prefix.'/'.$this->request->params['controller'].'/'.$this->request->params['action'],
                    $prefix.'/'.$this->request->params['controller'],
                    $prefix.'/'.$this->request->params['action']
                ]
            );
        }

        if ($param) {
            $activeTabs[] = $this->request->params['controller'].'/'.$this->request->params['action'].'/'.$this->request->params['pass'][0];
        }

        $activeTabs = array_merge(
            $activeTabs,
            [
                $this->request->params['controller'].'/'.$this->request->params['action'],
                $this->request->params['controller'],
                $this->request->params['action']
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
        $menuOptions = array_merge(
            [
                'id' => isset($this->params['pass'][0])
                    ? $this->params['pass'][0]
                    : null
                ,
                'title' => null,
                'name'  => null,
                'blackList' => [],
                'whiteList' => [],
                'controller' => $this->params['controller'],
                'style' => 'form-actions'
            ],
            $menuOptions
        );

        if (isset($menuOptions['prefix'])) {
            $prefix = $menuOptions['prefix'];
        } else {
            $prefix = null;
        }

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

        $output = '';
        $this->__actions = [];

        $this->__addActionButton(
            'save',
            $menuOptions,
            'submit',
            'fa fa-save',
            'Save',
            $class = 'btn btn-success'
        );
        $this->__addActionLink('modal_cancel', $menuOptions, $prefix, 'index', null, 'fa fa-undo', 'Cancel');
        $this->__addActionLink('cancel_add', $menuOptions, $prefix, 'index', null, 'fa fa-undo', 'Cancel');
        $this->__addActionLink('cancel_edit', $menuOptions, $prefix, 'view', $menuOptions['id'], 'fa fa-undo', 'Cancel');
        $this->__addActionLink('view', $menuOptions, $prefix, 'view', $menuOptions['id'], 'fa fa-zoom', 'View');
        $this->__addActionLink('edit', $menuOptions, $prefix, 'edit', $menuOptions['id'], 'fa fa-edit', 'Edit');
        $this->__addActionLink('delete', $menuOptions, $prefix, 'delete', $menuOptions['id'], 'fa fa-delete', 'Delete', 'Are you sure you want to delete '.$menuOptions['title'].'?', true);
        $this->__addActionLink('index', $menuOptions, $prefix, 'index', $menuOptions['id'], 'fa fa-list', 'List all');
        $this->__addActionLink('add', $menuOptions, $prefix, 'add', $menuOptions['id'], 'fa fa-plus-square', 'Add a new ' . $menuOptions['name']);

        $output = '';

        if ($this->__actions) {

            switch ($menuOptions['style']) {
                case 'form-actions':
                    $output = '<div class="form-actions"><div class="btn-group">';
                        foreach ($this->__actions as $action) {
                            $output .= $action;
                        }
                    $output .= '</div></div>';
                    break;
                case 'buttonbar':
                    $output = '<div class="btn-group">';
                        foreach ($this->__actions as $action) {
                            $output .= $action;
                        }
                    $output .= '</div>';
                    break;
                case 'pills':
                    $output = '<ul class="nav nav-pills nav-pills-actions">';
                        foreach ($this->__actions as $action) {
                            $output .= '<li>' . $action . '</li>';
                        }
                    $output .= '</ul>';
                    break;
                case 'links';
                case 'icon':
                    foreach ($this->__actions as $action) {
                        $output .= $action;
                    }
                    break;
            }

        }

        return $output;
    }

    private function __addActionLink($action, $menuOptions = [], $prefix = null, $targetAction, $id = null, $icon, $text, $promptText = null, $postLink = false)
    {

        if ($this->includeLink($action, $menuOptions)) {
            $url = [
                'controller' => $menuOptions['controller'],
                'action' => $targetAction,
                $id
            ];
            if ($prefix) {
                $url[$prefix] = true;
            }
            if ($postLink) {
                $this->__actions[] = $this->Form->postLink(
                    '<i class="' . $icon . '"></i> ' . $text,
                    $url,
                    [
                        'escape' => false,
                        'class' => $menuOptions['linkClass']
                    ],
                    $promptText
                );
            } else {
                if ($action == 'modal_cancel') {
                    $this->__actions[] = $this->Form->button(
                        'Cancel',
                        [
                            'type' => 'button',
                            'class' => 'btn btn-default',
                            'data-dismiss' => 'modal'
                        ]
                    );
                } else {
                    $this->__actions[] = $this->Html->link(
                        '<i class="' . $icon . '"></i> ' . $text,
                        $url,
                        [
                            'escape' => false,
                            'class' => $menuOptions['linkClass']
                        ],
                        $promptText
                    );
                }

            }
        }
    }

    private function __addActionButton($action, $menuOptions = [], $type = 'submit', $icon, $text, $class = 'btn')
    {
        if ($this->includeLink($action, $menuOptions)) {
            $this->__actions[] = $this->Form->button(
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

    private function dropdown($text, $dropdown, $params)
    {
        $output = '<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . $text . ' <b class="caret"></b></a>';
        $output .= '<ul class="dropdown-menu" role="menu">';
        $output .= $this->menu($dropdown, ['tag' => 'li']);
        $output .= '</ul>';

        return $output;

        return $this->Html->link(
            $text . '<b class="caret"></b><ul class="dropdown-menu" role="menu">'.$this->menu($dropdown).'</ul>',
            '#',
            [
                'class' => 'dropdown-toggle',
                'data-toggle' => 'dropdown',
                'escape' => false
            ]
        );
    }

    private function divider()
    {
        return '<li class="divider"></li>';
    }

}