<?php
abstract class boot_TemplateView extends mvc_HtmlView {
	abstract protected function goInnerBodyContent();

	protected function getTitle() {
		return 'Bootstrap Project';
	}

	protected function renderHeadMeta() {
		// Is there any point anymore?
	}

	protected function renderHeadCss() {
		if($this->get_css) {
			foreach($this->get_css as $css) {
				pfl('	<link rel="stylesheet" href="/%s" type="text/css" media="screen"></link>', $css);
			}
		}
	}

	protected function renderHeadJs() {
		pfl('	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>');
		if($this->get_js) {
			foreach($this->get_js as $script) {
				pfl('	<script type="text/javascript" src="/%s"></script>', $script);
			}
		}
	}

	protected function renderBodyContent() {
		pfl('	<div id="container"> 
				<div id="header">
					<h1>%s</h1>
				</div>
				<div id="body">', $this->get_title);
		$this->goInnerBodyContent();
		pfl('		</div>
			</div>');
	}

}
?>
