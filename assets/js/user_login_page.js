(() => {
	const leavesContainer = document.getElementById('leaves-container');
	const loginForm = document.getElementById('loginForm');
	const loginBtn = document.querySelector('.login-btn');
	const trees = Array.from(document.querySelectorAll('.tree'));
	const chairs = Array.from(document.querySelectorAll('.chair'));

	// --- Falling leaves ---
	let leafInterval = null;
	function spawnLeaf() {
		const leaf = document.createElement('div');
		leaf.className = 'leaf';

		const size = Math.floor(Math.random() * 20) + 12; // 12-32px
		const left = Math.random() * 110; // allow slight overflow
		const duration = (Math.random() * 8 + 6).toFixed(2); // 6-14s
		const delay = (Math.random() * 3).toFixed(2);
		const rotate = Math.floor(Math.random() * 360);

		leaf.style.width = size + 'px';
		leaf.style.height = Math.max(12, size - 4) + 'px';
		leaf.style.left = left + 'vw';
		leaf.style.top = '-8vh';
		leaf.style.opacity = (0.6 + Math.random() * 0.4).toFixed(2);
		leaf.style.transform = `rotate(${rotate}deg)`;
		leaf.style.animationDuration = duration + 's';
		leaf.style.animationDelay = delay + 's';

		leavesContainer.appendChild(leaf);

		// cleanup after animation
		const removeAfter = (parseFloat(duration) + parseFloat(delay) + 1) * 1000;
		setTimeout(() => { leaf.remove(); }, removeAfter);
	}

	function startLeaves() {
		stopLeaves();
		const isMobile = window.innerWidth <= 768;
		const rate = isMobile ? 900 : 450; // spawn interval
		leafInterval = setInterval(spawnLeaf, rate);
	}

	function stopLeaves() {
		if (leafInterval) clearInterval(leafInterval);
		leafInterval = null;
	}

	// --- Parallax ---
	function handlePointer(e) {
		const x = (e.clientX || (e.touches && e.touches[0].clientX) || window.innerWidth / 2) - window.innerWidth / 2;
		const y = (e.clientY || (e.touches && e.touches[0].clientY) || window.innerHeight / 2) - window.innerHeight / 2;

		const factor = 0.02; // subtle
		trees.forEach((el, i) => {
			const depth = 1 + i * 0.15;
			el.style.transform = `translate3d(${ -x * factor / depth }px, ${ -y * factor / (depth*1.5) }px, 0) rotate(${ (i%2?1:-1) * (x*0.0005) }deg)`;
		});
		chairs.forEach((el, i) => {
			const depth = 0.6 + i * 0.1;
			el.style.transform = `translate3d(${ -x * factor * 0.6 / depth }px, ${ -y * factor * 0.6 / depth }px, 0) rotate(${ (i%2?1:-1) * (x*0.0007) }deg)`;
		});
	}

	// --- Responsive adjustments on resize ---
	function handleResize() {
		const w = window.innerWidth;
		const form = document.querySelector('.login-form');
		if (!form) return;

		if (w <= 480) {
			form.style.width = '92%';
			form.style.padding = '22px';
			form.style.borderRadius = '14px';
			form.style.backdropFilter = 'blur(6px)';
		} else if (w <= 768) {
			form.style.width = '88%';
			form.style.padding = '28px';
			form.style.borderRadius = '16px';
			form.style.backdropFilter = 'blur(8px)';
		} else {
			form.style.width = '';
			form.style.padding = '';
			form.style.borderRadius = '';
			form.style.backdropFilter = '';
		}

		// restart leaves spawn with new rate
		startLeaves();
	}

	// --- Form submit animation ---
	function setButtonLoading(on) {
		if (on) {
			loginBtn.disabled = true;
			loginBtn.dataset.orig = loginBtn.innerHTML;
			loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing...';
			loginBtn.style.cursor = 'progress';
		} else {
			loginBtn.disabled = false;
			loginBtn.innerHTML = loginBtn.dataset.orig || 'Sign In';
			loginBtn.style.cursor = '';
		}
	}

	loginForm && loginForm.addEventListener('submit', (ev) => {
		ev.preventDefault();
		const form = ev.target;
		const inputs = Array.from(form.querySelectorAll('input'));
		const allFilled = inputs.every(i => i.value.trim().length > 0);

		if (!allFilled) {
			// small shake
			const f = document.querySelector('.login-form');
			f.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(-8px)' }, { transform: 'translateX(8px)' }, { transform: 'translateX(0)' }], { duration: 420 });
			return;
		}

		setButtonLoading(true);

		// submit the form after animation
		setTimeout(() => {
			loginBtn.innerHTML = '<i class="fas fa-check"></i> Welcome';
			loginBtn.style.background = '#2ecc71';
			// success pulse
			loginBtn.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.03)' }, { transform: 'scale(1)' }], { duration: 500 });

			setTimeout(() => {
				// Actually submit the form to the server
				form.submit();
			}, 1200);
		}, 1200);
	});

	// --- Input focus enhancements ---
	document.querySelectorAll('.input-group input').forEach(input => {
		input.addEventListener('focus', () => {
			input.style.boxShadow = '0 6px 18px rgba(0,0,0,0.25)';
			input.style.transform = 'translateY(-4px)';
		});
		input.addEventListener('blur', () => {
			input.style.boxShadow = '';
			input.style.transform = '';
		});
	});

	// --- Init ---
	window.addEventListener('mousemove', handlePointer);
	window.addEventListener('touchmove', handlePointer, { passive: true });
	window.addEventListener('resize', handleResize);
	handleResize();
	startLeaves();

	// Pause leaves when page not visible to save CPU
	document.addEventListener('visibilitychange', () => {
		if (document.hidden) stopLeaves(); else startLeaves();
	});

})();

