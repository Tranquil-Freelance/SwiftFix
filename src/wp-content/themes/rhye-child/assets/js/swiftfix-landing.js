(function () {
	'use strict';

	var toggle = document.querySelector('.sf-menu-toggle');
	var nav = document.getElementById('sf-nav-links');
	if (toggle && nav) {
		toggle.addEventListener('click', function () {
			var expanded = this.getAttribute('aria-expanded') === 'true';
			this.setAttribute('aria-expanded', String(!expanded));
			this.classList.toggle('sf-menu-toggle--active');
			nav.classList.toggle('sf-nav__links--open');
		});
		nav.querySelectorAll('a').forEach(function (link) {
			link.addEventListener('click', function () {
				toggle.setAttribute('aria-expanded', 'false');
				toggle.classList.remove('sf-menu-toggle--active');
				nav.classList.remove('sf-nav__links--open');
			});
		});
	}

	document.querySelectorAll('.sf a[href^="#"], .sf-scroll').forEach(function (link) {
		link.addEventListener('click', function (e) {
			var href = this.getAttribute('href');
			if (!href || href === '#' || href.length < 2) return;
			var target = document.querySelector(href);
			if (target) {
				e.preventDefault();
				e.stopPropagation();
				var navEl = document.querySelector('.sf-nav');
				var topbar = document.querySelector('.sf-topbar');
				var navHeight = navEl ? navEl.offsetHeight : 0;
				var topbarHeight = topbar ? topbar.offsetHeight : 0;
				var offset = target.getBoundingClientRect().top + window.pageYOffset - navHeight - topbarHeight - 20;
				window.scrollTo({ top: offset, behavior: 'smooth' });
				history.replaceState(null, '', href);
			}
		});
	});

	document.querySelectorAll('.sf a').forEach(function (link) {
		link.addEventListener('click', function (e) {
			var href = link.getAttribute('href') || '';
			if (href.indexOf('#') === 0 || href.indexOf('tel:') === 0 || href.indexOf('mailto:') === 0) {
				e.stopPropagation();
			}
		});
	});

	document.querySelectorAll('.sf-service-card').forEach(function (card) {
		card.style.cursor = 'pointer';
		card.addEventListener('click', function (e) {
			if (e.target.closest('a')) return;
			var link = card.querySelector('.sf-link');
			if (link) link.click();
		});
	});
})();
