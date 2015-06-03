( function ( M ) {

	var Button = M.require( 'Button' ),
		icons = M.require( 'icons' ),
		ButtonWithSpinner;

	/**
	 * @class ButtonWithSpinner
	 * @extends Button
	 *
	 * A button with loading state.
	 *
	 * To change the button loading state call `loading` with the state and
	 * re-render it.
	 * @example
	 *		<code>
	 *			var button = new ButtonWithSpinner( {
	 *				progressive: true,
	 *				label: 'My button',
	 *				additionalClassNames: 'my-button'
	 *			} );
	 *			button.loading( true );
	 *		</code>
	 */
	ButtonWithSpinner = Button.extend( {
		defaults: {
			tagName: 'button',
			disabled: false,
			loading: false,
			spinner: icons.spinner().toHtmlString()
		},
		/**
		 * Set the loading state of the button
		 * @param {Boolean} [loading] Button loading status. If not present
		 * defaults to the internal status
		 */
		loading: function ( loading ) {
			this.options.loading = loading || this.options.loading;
			this.update();
		},
		/**
		 * Enable/disable the button
		 * @param {Boolean} [disabled] Button disabled status. If not present
		 * defaults to the internal status
		 */
		disabled: function ( disabled ) {
			this.options.disabled = disabled;
			this.update();
		},
		/** @inheritdoc */
		postRender: function () {
			// FIXME: Remove postRender hack from Button and this if, and move the
			// classNames to the className property on the class when T97663 is
			// solved
			if ( !this.$el.hasClass( 'button-spinner' ) ) {
				Button.prototype.postRender.apply( this, arguments );
				this.$el.addClass( 'button-spinner mw-ui-input-inline' );
			}
			this.update();
		},
		/**
		 * Update the dom with the status of the button
		 */
		update: function () {
			var loading = this.options.loading,
				disabled = this.options.disabled,
				disabledProp = loading || disabled;
			this.$el.prop( 'disabled', disabledProp );
			this.$el.html( loading ? this.options.spinner : this.options.label );
		}
	} );

	M.define( 'ext.gather.buttonspinner/ButtonWithSpinner', ButtonWithSpinner );

}( mw.mobileFrontend, jQuery ) );
