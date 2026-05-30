import { NextResponse, type NextRequest } from "next/server";

export function middleware(request: NextRequest) {
  const isAuthed = request.cookies.get("sortlot_auth")?.value === "1";
  const isLogin = request.nextUrl.pathname === "/login";
  const isDashboard = request.nextUrl.pathname.startsWith("/dashboard");

  if (isDashboard && !isAuthed) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  if (isLogin && isAuthed) {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/login", "/dashboard/:path*"],
};
