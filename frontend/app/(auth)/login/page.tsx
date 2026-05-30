"use client";

import { useState } from "react";
import { zodResolver } from "@hookform/resolvers/zod";
import { AxiosError } from "axios";
import { LogIn } from "lucide-react";
import { useForm } from "react-hook-form";
import { z } from "zod";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useAuthStore } from "@/lib/stores/auth";

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

type LoginForm = z.infer<typeof loginSchema>;

export default function LoginPage() {
  const login = useAuthStore((state) => state.login);
  const isLoading = useAuthStore((state) => state.isLoading);
  const [error, setError] = useState<string | null>(null);
  const form = useForm<LoginForm>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: "", password: "" },
  });

  async function onSubmit(values: LoginForm) {
    setError(null);

    try {
      await login(values.email, values.password);
      window.location.assign("/dashboard");
    } catch (caught) {
      const message =
        caught instanceof AxiosError && caught.response?.status === 429
          ? "Too many login attempts. Try again soon."
          : "Email or password is incorrect.";

      setError(message);
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-background px-4">
      <Card className="w-full max-w-sm rounded-lg">
        <CardHeader>
          <CardTitle className="text-2xl">SortLot login</CardTitle>
        </CardHeader>
        <CardContent>
          <form className="space-y-4" onSubmit={form.handleSubmit(onSubmit)}>
            <input
              autoComplete="email"
              className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm outline-none transition-colors placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="admin@sortlot.local"
              type="email"
              {...form.register("email")}
            />
            <input
              autoComplete="current-password"
              className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm outline-none transition-colors placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="Password"
              type="password"
              {...form.register("password")}
            />
            {error ? <p className="text-sm text-destructive">{error}</p> : null}
            <button
              className="inline-flex h-9 w-full items-center justify-center gap-2 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground transition hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
              disabled={isLoading}
              type="submit"
            >
              <LogIn className="h-4 w-4" />
              {isLoading ? "Signing in" : "Sign in"}
            </button>
          </form>
        </CardContent>
      </Card>
    </main>
  );
}
