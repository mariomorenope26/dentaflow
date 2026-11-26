import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 36 36"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M11.5 5C8.1 5 5.5 7.9 5.5 11.7C5.5 15.1 7.1 17.5 8 21.8C8.8 25.6 9.8 30.5 12.6 30.5C14.7 30.5 15.3 25.9 18 25.9C20.7 25.9 21.3 30.5 23.4 30.5C26.2 30.5 27.2 25.6 28 21.8C28.9 17.5 30.5 15.1 30.5 11.7C30.5 7.9 27.9 5 24.5 5C21.8 5 20.1 6.7 18 6.7C15.9 6.7 14.2 5 11.5 5Z"
                stroke="currentColor"
                strokeWidth="2.4"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M13 13.5C14.3 15 16.1 15.7 18 15.7C19.9 15.7 21.7 15 23 13.5"
                stroke="currentColor"
                strokeWidth="2.2"
                strokeLinecap="round"
            />
        </svg>
    );
}
