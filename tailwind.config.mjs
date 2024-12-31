/** @type {import('tailwindcss').Config} */
export default {
	content: ['./src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}'],
	theme: {
		extend: {
			colors:{
				skyblue: "#3ABEF9",
				navy: "#050C9C",      // Dark navy blue
        		blue: "#3572EF",      // Medium blue
			}
		},
	},
	plugins: [],
}
