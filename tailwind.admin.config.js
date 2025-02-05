module.exports = {
    content: [
        './includes/admin/**/*.php', // First include admin files
        './resources/js/admin/**/*.js',
        './resources/css/safelist.txt',
    ],
	corePlugins: {
        container: false
    },
    theme: {
        extend: {
            colors: {
                'gold': 'var(--dc-gold)',
				'yellow': 'var(--dc-yellow)',
				'yellow-400': '#facc15',
				'border': 'var(--dc-border)',
				'light-blue': 'var(--dc-light-blue)',
				'light-blue-bg': 'var(--dc-light-blue-bg)',
				'dark-blue': 'var(--dc-dark-blue)',
				'dark-blue-10': 'var(--dc-dark-blue-10)',
				'dark-blue-20': 'var(--dc-dark-blue-20)',
				'hover-blue': 'var(--dc-hover-blue)',
				'grey': 'var(--dc-grey)',
				'dark-grey': 'var(--dc-dark-grey)'
            },
            boxShadow: {
                base: '0px 0px 20px 0px #E1E4ED',
            },
        },
        screens: {
            esm: '380px',
            sm: '640px',
            md: '768px',
            mdl: '980px',
            lg: '1024px',
            xl: '1140px',
            '2xl': '1280px',
            '3xl': '1380px',
            '4xl': '1480px',
            '7xl': '1700px',
            '9xl': '1980px',
        },
        fontSize: {
            sm: '0.8rem',
            medium: '1rem',
            base: '1.1rem',
            xl: '1.4rem',
            '2xl': '1.5rem',
            '3xl': '1.8rem',
            '4xl': '2.4rem',
            '5xl': '3.1rem',
        },
    },
};
