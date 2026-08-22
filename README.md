# AnVision Studio

Marketing site for **AnVision Studio**, a creative technology studio. Built with
[Next.js](https://nextjs.org) (App Router), TypeScript, and Tailwind CSS v4.

## Getting started

```bash
npm install       # install dependencies
npm run dev       # start the dev server on http://localhost:3000
```

## Available scripts

| Command         | Description                              |
| --------------- | ---------------------------------------- |
| `npm run dev`   | Start the development server (port 3000) |
| `npm run build` | Create a production build                |
| `npm start`     | Serve the production build               |
| `npm run lint`  | Run ESLint                               |

## Project structure

- `src/app/page.tsx` — landing page (server component)
- `src/app/components/WaitlistForm.tsx` — client-side waitlist form
- `src/app/api/waitlist/route.ts` — API route that validates and accepts signups

## Waitlist API

`POST /api/waitlist` with a JSON body `{ "email": "you@studio.com" }`.

- `200` — `{ ok: true, message }` on success
- `422` — `{ ok: false, error }` when the email is invalid
- `400` — `{ ok: false, error }` when the body is not valid JSON
