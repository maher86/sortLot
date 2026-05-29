export default function Home() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-zinc-950 px-6 text-zinc-50">
      <section className="w-full max-w-2xl">
        <p className="text-sm font-medium uppercase tracking-[0.24em] text-emerald-300">
          SortLot
        </p>
        <h1 className="mt-4 text-4xl font-semibold tracking-normal sm:text-6xl">
          Used clothing operations, ready for sorting.
        </h1>
        <p className="mt-6 max-w-xl text-base leading-7 text-zinc-300">
          The Phase 1 foundation is running with Next.js, Laravel, MySQL,
          Redis, Mailhog, and MinIO behind Docker.
        </p>
      </section>
    </main>
  );
}
