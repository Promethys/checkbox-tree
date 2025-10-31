module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './src/**/*.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
