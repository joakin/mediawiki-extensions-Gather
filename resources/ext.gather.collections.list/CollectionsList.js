import $ from '../jquery.js';
import CreateCollectionButton from '../ext.gather.collections.list/CreateCollectionButton.js';

import CollectionsListTemplate from '../../templates/CollectionsList.mustache';
import CollectionsListItemCardTemplate from '../../templates/CollectionsListItemCard.mustache';
import CardImageTemplate from '../../templates/CardImage.mustache';

const CollectionsApi = mw.mobileFrontend.require( 'ext.gather.api/CollectionsApi' ),
	InfiniteScroll = mw.mobileFrontend.require( 'InfiniteScroll' ),
	icons = mw.mobileFrontend.require( 'icons' ),
	toast = mw.mobileFrontend.require( 'toast' ),
	View = mw.mobileFrontend.require( 'View' ),
	Icon = mw.mobileFrontend.require( 'Icon' );

export default View.extend( {
	/** @inheritdoc */
	defaults: {
		collections: [],
		// FIXME: Use the icon partials in server and client when supported in server templates.
		userIconClass: new Icon( {
			name: 'profile',
			hasText: true
		} ).getClassName()
	},
	template: CollectionsListTemplate,
	templatePartials: {
		item: CollectionsListItemCardTemplate,
		image: CardImageTemplate
	},
	/** @inheritdoc */
	initialize () {
		View.prototype.initialize.apply( this, arguments );
		// After the initial render initialize the infinite scrolling.
		this.$pagination = this.$el.find( '.collections-pagination' );
		if ( this.$pagination.length ) {
			this._replacePaginationControls();
			this.api = new CollectionsApi();
			this.infiniteScroll = new InfiniteScroll();
			this.infiniteScroll.setElement( this.$el );
			this.infiniteScroll.on( 'load', $.proxy( this, '_loadCollections' ) );
		}
	},
	/** @inheritdoc */
	postRender () {
		// Look for rendered list in the dom
		let $collectionsList = $( '.collections-list' );
		// Add a create button at the bottom if the list owner is viewing in minerva skin
		if ( $collectionsList.data( 'is-owner' ) && mw.config.get( 'skin' ) === 'minerva' ) {
			new CreateCollectionButton( {} )
				.appendTo( $collectionsList.find( '.collection-actions' ) );
		}
		View.prototype.postRender.apply( this, arguments );
	},
	/**
		* Replace html link pagination controls with components for the infinite
		* scrolling
		*/
	_replacePaginationControls () {
		this.continueArgs = {
			lstcontinue: this._parseContinueUrl(
				this.$pagination.children( 'a' ).attr( 'href' )
			)
		};
		this.$pagination.html( icons.spinner().toHtmlString() );
		this.$pagination.hide();
	},
	/**
		* Parse the pagination href to get the continue param
		* @param {String} url to parse
		* @return {String} continue parameter
		*/
	_parseContinueUrl ( url ) {
		let params = url.split( '?' )[ 1 ].split( '&' ),
			param = null;
		$.each( params, ( i, p ) => {
			if ( p.indexOf( 'lstcontinue=' ) !== -1 ) {
				param = decodeURIComponent( p.split( '=' )[ 1 ] );
			}
		} );
		return param;
	},
	/**
	 * Load more collections from the API
	 */
	_loadCollections () {
		if ( this.continueArgs ) {
			this.$pagination.show();
			this._apiCallByMode()
			.always( () => {
				this.$pagination.hide();
				this.infiniteScroll.enable();
			} )
			.done( ( data ) => {
				this.continueArgs = data.continueArgs || false;
				this.renderCollections( data.collections );
			} )
			.fail( () =>
				toast.show( mw.msg( 'gather-lists-more-failed' ), 'toast error' ) );
		}
	},
	/**
		* Call the api depending on the collectionslist mode
		* @return {jQuery.Deferred} Contains a list of collections
		*/
	_apiCallByMode () {
		if ( this.options.mode === 'recent' ) {
			return this.api.getCollections( null, $.extend( this.continueArgs, {
					lstminitems: 4,
					lstmode: 'allpublic'
				} ) );
		} else {
			return this.api.getCurrentUsersCollections( this.options.owner, null, this.continueArgs );
		}
	},
	/**
		* Render collections into the view.
		* @param {Array} collections to render
		*/
	renderCollections ( collections ) {
		this.$pagination.before(
			$.map( collections, ( coll ) => {
				this.templatePartials.item.render(
					$.extend( {}, coll, {
						langdir: 'ltr',
						articleCountMsg: mw.msg( 'gather-article-count', coll.count ),
						// If the collection has an owner, don't show it in the cards.
						owner: Boolean( this.options.owner ) ? null : {
							label: coll.owner,
							link: this._getOwnerUrl( coll.owner ),
							className: this.options.userIconClass
						},
						privacyMsg: this._getPrivacyMsg( coll.perm ),
						collectionUrl: this._getUrl( coll.id ),
						hasImage: Boolean( coll.image ),
						image: this.templatePartials.image.render( {
							url: coll.imageurl,
							wide: coll.imagewidth > coll.imageheight
						} )
					} )
				);
			} )
		);
	},
	/**
		* Get the owner url
		* @param {String} name of the owner
		* @return {String}
		*/
	_getOwnerUrl ( name ) {
		return mw.util.getUrl( [ 'Special:Gather', 'by', name ].join( '/' ) );
	},
	/**
		* Get the url for a collection
		* @param {Number} id of the collection
		* @return {String}
		*/
	_getUrl ( id ) {
		return mw.util.getUrl( [ 'Special:Gather', 'id', id ].join( '/' ) );
	},
	/**
		* Return privacy message depending on collection perm
		* @param {String} perm status of the collection
		* @return {String}
		*/
	_getPrivacyMsg ( perm ) {
		switch ( perm ) {
			case 'public': return mw.msg( 'gather-public' );
			case 'private': return mw.msg( 'gather-private' );
			case 'hidden': return mw.msg( 'gather-hidden' );
		}
	}
} );
