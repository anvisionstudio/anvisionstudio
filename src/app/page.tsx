import { WaitlistForm } from "./components/WaitlistForm";

const services = [
  {
    title: "Brand & Identity",
    description:
      "Distinct visual systems, logotypes, and guidelines that make brands unmistakable.",
  },
  {
    title: "Motion Design",
    description:
      "Story-driven animation and 3D motion for launches, campaigns, and product reveals.",
  },
  {
    title: "Interactive Web",
    description:
      "High-performance sites and experiences built with modern web technology.",
  },
];

export default function Home() {
  return (
    <div className="relative flex flex-1 flex-col overflow-hidden">
      <div className="pointer-events-none absolute inset-0 -z-10">
        <div className="aurora absolute -left-40 top-[-10rem] h-[36rem] w-[36rem] rounded-full bg-accent/30 blur-[120px]" />
        <div className="aurora absolute right-[-12rem] top-40 h-[32rem] w-[32rem] rounded-full bg-accent-soft/20 blur-[120px] [animation-delay:-6s]" />
      </div>

      <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
        <span className="text-lg font-semibold tracking-tight">
          An<span className="text-accent">Vision</span> Studio
        </span>
        <nav className="hidden gap-8 text-sm text-white/60 sm:flex">
          <a className="transition hover:text-white" href="#work">
            Work
          </a>
          <a className="transition hover:text-white" href="#services">
            Services
          </a>
          <a className="transition hover:text-white" href="#waitlist">
            Contact
          </a>
        </nav>
      </header>

      <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center px-6 py-16">
        <section className="max-w-3xl">
          <p className="mb-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs font-medium uppercase tracking-widest text-white/70">
            Creative technology studio
          </p>
          <h1 className="text-4xl font-semibold leading-tight tracking-tight sm:text-6xl">
            We craft{" "}
            <span className="bg-gradient-to-r from-accent to-accent-soft bg-clip-text text-transparent">
              design, motion &amp; interactive
            </span>{" "}
            experiences.
          </h1>
          <p className="mt-6 max-w-xl text-lg text-white/60">
            AnVision Studio partners with ambitious teams to turn bold ideas into
            memorable digital products — from identity to launch.
          </p>
        </section>

        <section id="waitlist" className="mt-10">
          <p className="mb-3 text-sm font-medium text-white/70">
            Join the waitlist for our next intake of projects.
          </p>
          <WaitlistForm />
        </section>

        <section
          id="services"
          className="mt-20 grid gap-6 sm:grid-cols-3"
        >
          {services.map((service) => (
            <div
              key={service.title}
              className="rounded-2xl border border-white/10 bg-white/[0.03] p-6 transition hover:border-accent/40 hover:bg-white/[0.06]"
            >
              <h2 className="text-base font-semibold text-white">
                {service.title}
              </h2>
              <p className="mt-2 text-sm leading-6 text-white/55">
                {service.description}
              </p>
            </div>
          ))}
        </section>
      </main>

      <footer className="mx-auto w-full max-w-6xl px-6 py-8 text-sm text-white/40">
        © {new Date().getFullYear()} AnVision Studio. All rights reserved.
      </footer>
    </div>
  );
}
