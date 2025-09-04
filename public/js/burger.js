document.addEventListener('DOMContentLoaded', () => {
	
	// simple toggle with ARIA + Escape to close
	const btn = document.querySelector('.nav-toggle');
	const menu = document.getElementById('site-menu');

	function openMenu() {
	  menu.classList.add('is-open');
	  document.body.classList.add('menu-open');
	  btn.setAttribute('aria-expanded', 'true');
	}

	function closeMenu() {
	  menu.classList.remove('is-open');
	  document.body.classList.remove('menu-open');
	  btn.setAttribute('aria-expanded', 'false');
	}

	btn.addEventListener('click', () => {
	  const isOpen = menu.classList.contains('is-open');
	  isOpen ? closeMenu() : openMenu();
	});

	menu.addEventListener('click', (e) => {
	  // close when a link is clicked
	  if (e.target.tagName === 'A') closeMenu();
	});

	window.addEventListener('keydown', (e) => {
	  if (e.key === 'Escape') closeMenu();
	});

});