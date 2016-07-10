<?php

class NavigationHelper extends AppHelper {

	var $helpers = array('Html', 'Form', 'Session');

	public function menu($menuName = null, $menuOptions = array()) {

		if (!$menuName) return '';

		Configure::load('menus');

		$this->menu = Configure::read('menus.'.$menuName);

		if (!$this->menu || empty($this->menu)) return '';

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

			if (!isset($menuItem['Auth'][$roleId]) || $menuItem['Auth'][$roleId] != 'deny') {
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
							$this->__dropdown(
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
								isset($menuItem['options']) ? $menuItem['options'] : array()
							),
							isset($menuItem['tag_options']) ? $menuItem['tag_options'] : array()
						);
					}
				} else {
					$output .= $this->Html->link(
						$menuItem['text'],
						$menuItem['url'],
						isset($menuItem['options']) ? $menuItem['options'] : array()
					);
				}

				if (isset($menuItem['divide'])) {
					$output .= $this->__divider(); //'<li class="divider"></li>';
				}
			}
		}

		return $output;

	}

	private function setActiveTab($menuOptions = array()) {

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

		$activeTabs = array();

		if ($prefix) {
			if ($param) {
				$activeTabs[] = $prefix.'/'.$this->request->params['controller'].'/'.$this->request->params['action'].'/'.$param;
			}
			$activeTabs = array_merge(
				$activeTabs,
				array(
					$prefix.'/'.$this->request->params['controller'].'/'.$this->request->params['action'],
					$prefix.'/'.$this->request->params['controller'],
					$prefix.'/'.$this->request->params['action']
				)
			);
		}

		if ($param) {
			$activeTabs[] = $this->request->params['controller'].'/'.$this->request->params['action'].'/'.$this->request->params['pass'][0];
		}

		$activeTabs = array_merge(
			$activeTabs,
			array(
				$this->request->params['controller'].'/'.$this->request->params['action'],
				$this->request->params['controller'],
				$this->request->params['action']
			)
		);

		$found = false;
		for ($i = 0, $len = count($activeTabs); $i < $len && !$found; $i++) {
			$active = $activeTabs[$i];
			$found = $this->activeTabExists($active);
		}

	}

	private function activeTabExists($active) {
		if (isset($this->menu[$active])) {
			$this->menu[$active]['tag_options'] = array('class' => 'active');
			return true;
		}
		return false;
	}

	public function quickLinks($menuName = null) {

		if (!$menuName) return null;

		return $this->Html->tag(
			'ul',
			$this->menu(
				$menuName,
				array('tag' => 'li')
			),
			array('class' => 'masthead-links')
		);

	}

	private function __includeLink($linkName = null, $linkOptions = array()) {

		if (isset($linkOptions['blackList'][$linkName])) {
			return false;
		} elseif (isset($linkOptions['whiteList'][$linkName])) {
			return true;
		} elseif (isset($linkOptions['greyList'][$linkName]) && !isset($linkOptions['whiteList'][$linkName])) {
			return false;
		} elseif (!empty($linkOptions['whiteList']) && !isset($linkOptions['whiteList'][$linkName])) {
			return false;
		}

		return true;

	}

	public function actions($menuOptions = array()) {

		$menuOptions = array_merge(
			array(
				'id' => isset($this->params['pass'][0])
					? $this->params['pass'][0]
					: null
				,
				'title' => null,
				'name'  => null,
				'blackList' => array(),
				'whiteList' => array(),
				'controller' => $this->params['controller'],
				'style' => 'form-actions'
			),
			$menuOptions
		);

		if (isset($menuOptions['prefix'])) {
			$prefix = $menuOptions['prefix'];
		} else {
			$prefix = null;
		}

		if (in_array($menuOptions['style'], array('form-actions', 'buttonbar'))) {
			$menuOptions['linkClass'] = 'btn';
		} elseif ($menuOptions['style'] == 'icon') {
			$menuOptions['linkClass'] = 'btn-icon';
		} else {
			$menuOptions['linkClass'] = 'index-link';
		}

		// if the white list is populated only use these items
		if (!empty($menuOptions['whiteList'])) {
			$menuOptions['whiteList'] = array_flip($menuOptions['whiteList']);
		}

		// if something is in the black list, don't use it
		if (!empty($menuOptions['blackList'])) {
			$menuOptions['blackList'] = array_flip($menuOptions['blackList']);
		}

		// something in the grey list is only added if it is in the white list
		$menuOptions['greyList'] = array(
			'save' => 0,
			'view' => 0,
			'cancel_add' => 0,
			'cancel_edit' => 0,
			'cancel_add_ajax' => 0,
			'modal_cancel' => 0
		);

		$output = '';
		$this->__actions = array();

		$this->__addActionButton(
			'save',
			$menuOptions,
			'submit',
			'icon-save',
			'Save',
			$class = 'btn btn-success'
		);
		$this->__addActionLink('modal_cancel', $menuOptions, $prefix, 'index', null, 'icon-undo', 'Cancel');
		$this->__addActionLink('cancel_add', $menuOptions, $prefix, 'index', null, 'icon-undo', 'Cancel');
		$this->__addActionLink('cancel_edit', $menuOptions, $prefix, 'view', $menuOptions['id'], 'icon-undo', 'Cancel');
		$this->__addActionLink('view', $menuOptions, $prefix, 'view', $menuOptions['id'], 'icon-zoom-in', 'View');
		$this->__addActionLink('edit', $menuOptions, $prefix, 'edit', $menuOptions['id'], 'icon-edit', 'Edit');
		$this->__addActionLink('delete', $menuOptions, $prefix, 'delete', $menuOptions['id'], 'icon-cut', 'Delete', 'Are you sure you want to delete '.$menuOptions['title'].'?', true);
		$this->__addActionLink('index', $menuOptions, $prefix, 'index', $menuOptions['id'], 'icon-list', 'List all');
		$this->__addActionLink('add', $menuOptions, $prefix, 'add', $menuOptions['id'], 'icon-plus-sign', 'Add a new ' . $menuOptions['name']);

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

	private function __addActionLink($action, $menuOptions = array(), $prefix = null, $targetAction, $id = null, $icon, $text, $promptText = null, $postLink = false) {

		if ($this->__includeLink($action, $menuOptions)) {
			$url = array(
				'controller' => $menuOptions['controller'],
				'action' => $targetAction,
				$id
			);
			if ($prefix) {
				$url[$prefix] = true;
			}
			if ($postLink) {
				$this->__actions[] = $this->Form->postLink(
					'<i class="' . $icon . '"></i> ' . $text,
					$url,
					array(
						'escape' => false,
						'class' => $menuOptions['linkClass']
					),
					$promptText
				);
			} else {
				if ($action == 'modal_cancel') {
					$this->__actions[] = $this->Form->button(
						'Cancel',
						array(
							'type' => 'button',
							'class' => 'btn btn-default',
							'data-dismiss' => 'modal'
						)
					);
				} else {
					$this->__actions[] = $this->Html->link(
						'<i class="' . $icon . '"></i> ' . $text,
						$url,
						array(
							'escape' => false,
							'class' => $menuOptions['linkClass']
						),
						$promptText
					);
				}

			}
		}
	}

	private function __addActionButton($action, $menuOptions = array(), $type = 'submit', $icon, $text, $class = 'btn') {
		if ($this->__includeLink($action, $menuOptions)) {
			$this->__actions[] = $this->Form->button(
				'<i class="' . $icon . '"></i> ' . $text,
				array(
					'type' => $type,
					'escape' => false,
					'class' => $class
				)
			);
		}
	}

	public function user_entry() {

		if ($this->Session->check('Auth.User')) {
			return $this->user_entry_logged_in();
		} else {
			return $this->user_entry_logged_out();
		}

	}

	public function user_entry_logged_out() {

		return $this->Html->tag(
			'ul',
			$this->menu(
				'user_entry_logged_out',
				array('tag' => 'li')
			),
			array('class' => 'nav pull-right')
		);

	}

	public function user_entry_logged_in() {

		return $this->Html->tag(
			'ul',
			$this->menu(
				'user_entry_logged_in',
				array('tag' => 'li')
			),
			array('class' => 'nav pull-right')
		);

	}

	private function __dropdown($text, $dropdown, $params) {

		$output = '<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . $text . ' <b class="caret"></b></a>';
		$output .= '<ul class="dropdown-menu" role="menu">';
		$output .= $this->menu($dropdown, array('tag' => 'li'));
		$output .= '</ul>';

		return $output;

		return $this->Html->link(
			$text . '<b class="caret"></b><ul class="dropdown-menu" role="menu">'.$this->menu($dropdown).'</ul>',
			'#',
			array(
				'class' => 'dropdown-toggle',
				'data-toggle' => 'dropdown',
				'escape' => false
			)
		);
	}

	private function __divider() {
		return '<li class="divider"></li>';
	}

}