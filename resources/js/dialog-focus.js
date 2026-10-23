export function dialogFocus() {
    return {
        opener: null,

        init() {
            this.opener = document.activeElement;
            this.$nextTick(() => this.$el.querySelector('input:not([disabled]), button:not([disabled])')?.focus());
        },

        destroy() {
            this.opener?.focus();
        },

        trap(event) {
            const items = [...this.$el.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])')]
                .filter((item) => item.offsetParent !== null);

            if (!items.length) return;

            if (event.shiftKey && document.activeElement === items[0]) {
                event.preventDefault();
                items.at(-1).focus();
            } else if (!event.shiftKey && document.activeElement === items.at(-1)) {
                event.preventDefault();
                items[0].focus();
            }
        },
    };
}
