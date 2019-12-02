<?php
namespace sv_tracking_manager;

/**
 * @version         1.000
 * @author			straightvisions GmbH
 * @package			sv_tracking_manager
 * @copyright		2019 straightvisions GmbH
 * @link			https://straightvisions.com
 * @since			1.000
 * @license			See license.txt or https://straightvisions.com
 */

class custom extends modules {
	private $base_path					= false;
	private $base_url					= false;

	public function init() {
		$this->base_path				= trailingslashit(trailingslashit(wp_upload_dir()['basedir']).$this->get_root()->get_prefix());
		$this->base_url					= trailingslashit(trailingslashit(wp_upload_dir()['baseurl']).$this->get_root()->get_prefix());

		// Section Info
		$this->set_section_title( __('Custom Scripts', 'sv_tracking_manager' ) )
			->set_section_desc(__( 'Setup custom tracking scripts.', 'sv_tracking_manager' ))
			->set_section_type( 'settings' )
			->load_settings()
			->register_scripts()
			->get_root()->add_section( $this );

		$this->add_service();
	}
	public function is_active(): bool{
		// no script set
		if(!$this->get_setting('custom_scripts')->run_type()->get_data()){
			return false;
		}
		// no script set
		if(!is_array($this->get_setting('custom_scripts')->run_type()->get_data())){
			return false;
		}
		// no script set
		if(is_array($this->get_setting('custom_scripts')->run_type()->get_data()) && count($this->get_setting('custom_scripts')->run_type()->get_data()) === 0){
			return false;
		}

		return true;
	}
	protected function load_settings(): custom {
		// Events Groups
		$this->get_setting('custom_scripts')
			->set_title(__('Custom Scripts', 'sv_tracking_manager'))
			->load_type('group');

		$this->get_setting('custom_scripts')->run_type()->add_child($this)
			->set_ID('entry_label')
			->set_title(__('Entry Label', 'sv_tracking_manager'))
			->set_description(__('This Label will be used as Entry Title for this Settings Group. When using Usercentrics, use the exact same label for the Script as Usercentrics.', 'sv_tracking_manager'))
			->load_type('text')
			->set_placeholder('Entry #...');

		$this->get_setting('custom_scripts')->run_type()->add_child($this)
			->set_ID('id')
			->set_title(__('ID', 'sv_tracking_manager'))
			->set_description(__('Each Script needs an unique ID.', 'sv_tracking_manager'))
			->load_type('text');

		$this->get_setting('custom_scripts')->run_type()->add_child($this)
			->set_ID('url')
			->set_title(__('URL', 'sv_tracking_manager'))
			->set_description(__('Script will be attached from this URL', 'sv_tracking_manager'))
			->load_type('url');

		$this->get_setting('custom_scripts')->run_type()->add_child($this)
			->set_ID('snippet')
			->set_title(__('Snippet', 'sv_tracking_manager'))
			->set_description(__('Snippet will be saved in a .JS-file and attached.', 'sv_tracking_manager'))
			->load_type('textarea');

		add_action('update_option_'.$this->get_setting('custom_scripts')->run_type()->get_field_id(), array($this, 'setting_updated'), 10, 2);

		return $this;
	}
	protected function register_scripts(): custom {
		if($this->is_active()){
			foreach($this->get_setting('custom_scripts')->run_type()->get_data() as $script){
				if(strlen($script['id']) > 0 && strlen($script['url']) > 0){
					$this->get_script($script['id'])
						->set_path($script['url'])
						->set_type('js')
						->set_is_enqueued();
				}
				if(strlen($script['id']) > 0 && strlen($script['snippet']) > 0){
					$this->get_script($script['id'])
						->set_path($this->get_file_path($script['id']), true, $this->get_file_url($script['id']))
						->set_type('js')
						->set_is_enqueued();
				}

				if($this->get_parent()->usercentrics->is_active()) {
					$this->get_script($script['id'])
						->set_consent_required()
						->set_custom_attributes(' data-usercentrics="'.$script['entry_label'].'"');
				}
			}
		}

		return $this;
	}
	public function setting_updated($old_value, $value){
		foreach($value as $script){
			if(strlen($script['id']) > 0 && strlen($script['snippet']) > 0) {
				$this->save($script['id'], $script['snippet']);
			}
		}
	}
	public function get_base_path(): string{
		return $this->base_path;
	}
	public function get_base_url(): string{
		return $this->base_url;
	}
	public function get_file_path(string $ID): string{
		return $this->get_base_path().md5($ID).'.js';
	}
	public function get_file_url(string $ID): string{
		return $this->get_base_url().md5($ID).'.js';
	}
	public function save(string $ID, string $content): bool{
		if(!is_dir($this->get_base_path())){
			mkdir($this->get_base_path());
		}
		if(file_put_contents($this->get_file_path($ID),$content) !== false){
			return true;
		}else{
			return false;
		}
	}
}