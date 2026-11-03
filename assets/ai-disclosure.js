/* global wpAiDisclosure */
( function () {
	'use strict';
	if ( typeof wpAiDisclosure === 'undefined' ) { return; }
	var cfg = wpAiDisclosure;
	var KEY = 'wpAiDisclosureDismissed';
	var shown = false;

	function dismissed() {
		try { return cfg.dismissible && window.sessionStorage.getItem( KEY ) === '1'; } catch ( e ) { return false; }
	}

	function logHit() {
		if ( ! cfg.ajaxUrl || ! navigator.sendBeacon ) { return; }
		var data = new FormData();
		data.append( 'action', 'wp_ai_disclosure_hit' );
		navigator.sendBeacon( cfg.ajaxUrl, data );
	}

	function mount( anchor ) {
		if ( shown || dismissed() || document.getElementById( 'wp-ai-disclosure' ) ) { return; }
		var tpl = document.createElement( 'template' );
		tpl.innerHTML = cfg.html.trim();
		var node = tpl.content.firstElementChild;
		if ( ! node ) { return; }
		if ( cfg.position === 'inline' && anchor && anchor.parentNode ) {
			anchor.parentNode.insertBefore( node, anchor );
		} else {
			document.body.appendChild( node );
		}
		var btn = node.querySelector( '.wp-ai-disclosure__dismiss' );
		if ( btn ) {
			btn.addEventListener( 'click', function () {
				node.remove();
				try { window.sessionStorage.setItem( KEY, '1' ); } catch ( e ) { /* private mode */ }
			} );
		}
		shown = true;
		logHit();
	}

	function findAnchor() {
		for ( var i = 0; i < cfg.selectors.length; i++ ) {
			try {
				var el = document.querySelector( cfg.selectors[ i ] );
				if ( el ) { return el; }
			} catch ( e ) { /* invalid selector from settings */ }
		}
		return null;
	}

	function serverDetected() {
		var tag = document.getElementById( 'wp-ai-disclosure-detected' );
		if ( ! tag ) { return false; }
		try { return JSON.parse( tag.textContent || '[]' ).length > 0; } catch ( e ) { return false; }
	}

	function run() {
		if ( cfg.mode === 'manual' ) { return; }
		if ( cfg.mode === 'always' ) { mount( findAnchor() ); return; }

		var anchor = findAnchor();
		if ( anchor || serverDetected() ) { mount( anchor ); return; }

		// Widgets often inject themselves late; watch the DOM for up to 15 s.
		var observer = new MutationObserver( function () {
			var a = findAnchor();
			if ( a ) { observer.disconnect(); mount( a ); }
		} );
		observer.observe( document.documentElement, { childList: true, subtree: true } );
		setTimeout( function () { observer.disconnect(); }, 15000 );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', run );
	} else {
		run();
	}
} )();
