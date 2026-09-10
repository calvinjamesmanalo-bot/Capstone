<div class="rounded-lg border border-slate-200 bg-slate-50 p-3" data-password-requirements data-password-input="password">
    <p class="text-xs font-semibold text-slate-600" data-password-message>
        Password must meet all requirements below.
    </p>
    <ul class="mt-2 grid gap-1 text-xs text-slate-500 sm:grid-cols-2">
        <li data-password-rule="length">○ At least 12 characters</li>
        <li data-password-rule="case">○ Uppercase and lowercase letters</li>
        <li data-password-rule="number">○ At least one number</li>
        <li data-password-rule="symbol">○ At least one symbol</li>
    </ul>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-password-requirements]').forEach((panel) => {
                const input = document.getElementById(panel.dataset.passwordInput);
                const message = panel.querySelector('[data-password-message]');
                if (!input || !message) return;

                const checks = {
                    length: (value) => value.length >= 12,
                    case: (value) => /[a-z]/.test(value) && /[A-Z]/.test(value),
                    number: (value) => /\d/.test(value),
                    symbol: (value) => /[^A-Za-z0-9\s]/.test(value),
                };

                const updateStrength = () => {
                    const value = input.value;
                    let passed = 0;

                    Object.entries(checks).forEach(([name, check]) => {
                        const item = panel.querySelector(`[data-password-rule="${name}"]`);
                        const valid = check(value);
                        passed += valid ? 1 : 0;
                        item.textContent = `${valid ? '✓' : '○'} ${item.textContent.replace(/^[✓○]\s*/, '')}`;
                        item.className = valid ? 'font-medium text-emerald-700' : 'text-slate-500';
                    });

                    if (value === '') {
                        message.textContent = 'Password must meet all requirements below.';
                        message.className = 'text-xs font-semibold text-slate-600';
                    } else if (value.length < 12) {
                        message.textContent = 'Password is too short.';
                        message.className = 'text-xs font-semibold text-red-700';
                    } else if (passed < 4) {
                        message.textContent = 'Password is too weak.';
                        message.className = 'text-xs font-semibold text-amber-700';
                    } else {
                        message.textContent = 'Strong password.';
                        message.className = 'text-xs font-semibold text-emerald-700';
                    }
                };

                input.addEventListener('input', updateStrength);
                updateStrength();
            });
        });
    </script>
@endonce
