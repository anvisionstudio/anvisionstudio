"use client";

import { useState } from "react";

type Status =
  | { state: "idle" }
  | { state: "loading" }
  | { state: "success"; message: string }
  | { state: "error"; message: string };

export function WaitlistForm() {
  const [email, setEmail] = useState("");
  const [status, setStatus] = useState<Status>({ state: "idle" });

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setStatus({ state: "loading" });

    try {
      const response = await fetch("/api/waitlist", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email }),
      });
      const data = (await response.json()) as {
        ok: boolean;
        message?: string;
        error?: string;
      };

      if (response.ok && data.ok) {
        setStatus({ state: "success", message: data.message ?? "You're on the list." });
        setEmail("");
      } else {
        setStatus({
          state: "error",
          message: data.error ?? "Something went wrong. Please try again.",
        });
      }
    } catch {
      setStatus({
        state: "error",
        message: "Network error. Please try again.",
      });
    }
  }

  return (
    <div className="w-full max-w-md">
      <form onSubmit={handleSubmit} className="flex flex-col gap-3 sm:flex-row">
        <input
          type="email"
          name="email"
          required
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          placeholder="you@studio.com"
          aria-label="Email address"
          className="flex-1 rounded-full border border-white/15 bg-white/5 px-5 py-3 text-sm text-foreground outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/40"
        />
        <button
          type="submit"
          disabled={status.state === "loading"}
          className="rounded-full bg-gradient-to-r from-accent to-accent-soft px-6 py-3 text-sm font-semibold text-[#06070d] transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {status.state === "loading" ? "Joining…" : "Join the waitlist"}
        </button>
      </form>

      <div className="min-h-6 pt-3 text-sm" aria-live="polite">
        {status.state === "success" && (
          <p data-testid="waitlist-success" className="text-accent-soft">
            {status.message}
          </p>
        )}
        {status.state === "error" && (
          <p data-testid="waitlist-error" className="text-rose-400">
            {status.message}
          </p>
        )}
      </div>
    </div>
  );
}
