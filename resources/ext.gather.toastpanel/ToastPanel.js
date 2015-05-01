/*jshint unused:vars */
( function ( M, $ ) {

	var ToastPanel,
		Button = M.require( 'Button' ),
		Panel = M.require( 'Panel' );

	/**
	 * API for managing collection items
	 *
	 * @class ToastPanel
	 * @extends Panel
	 */
	ToastPanel = Panel.extend( {
		className: 'panel view-border-box drawer position-fixed toast-panel',
		template: mw.template.get( 'ext.gather.toastpanel', 'ToastPanel.hogan' ),
		templatePartials: {
			button: Button.prototype.template
		},
		/**
		 * @cfg {Object} defaults Default options hash.
		 * @cfg {Number} defaults.hideDuration in milliseconds to autohide if enabled
		 * @cfg {Boolean} defaults.autohide whether to autohide the notification
		 * @cfg {String} defaults.msg to show
		 * @cfg {Object[]} defaults.actions a list of options for a Button View.
		 */
		defaults: {
			hideDuration: 5000,
			autohide: true,
			msg: 'This page has been added to your "albums" collection',
			actions: []
		},
		/** @inheritdoc */
		show: function () {
			var self = this;
			Panel.prototype.show.apply( this, arguments );
			if ( this.options.autohide ) {
				window.clearTimeout( this._timeout );
				this._timeout = setTimeout( function () {
					self.hide();
				}, this.options.hideDuration );
			}
		}
	} );

	M.define( 'ext.gather.toastpanel/ToastPanel', ToastPanel );

}( mw.mobileFrontend, jQuery ) );
