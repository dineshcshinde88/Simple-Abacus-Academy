import { createContext, ReactNode, useContext, useEffect, useMemo, useState } from "react";
import { AuthApiError, AuthUser, RegisterDetails, forgotPassword as forgotPasswordApi, getMe, login as loginApi, register as registerApi } from "@/lib/auth";

type AuthContextType = {
  user: AuthUser | null;
  loading: boolean;
  token: string | null;
  login: (email: string, password: string, role: "student" | "tutor") => Promise<void>;
  register: (name: string, email: string, password: string, role: "student" | "tutor", details?: RegisterDetails) => Promise<void>;
  forgotPassword: (email: string) => Promise<void>;
  logout: () => void;
};

const AuthContext = createContext<AuthContextType | null>(null);
const TOKEN_KEY = "abacus_auth_token";
const SESSION_REPLACED_KEY = "student_session_replaced";
const STUDENT_SESSION_CHECK_MS = 15_000;

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_KEY));
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let sessionCheckTimer: number | undefined;
    let cancelled = false;

    const clearAuth = () => {
      localStorage.removeItem(TOKEN_KEY);
      setToken(null);
      setUser(null);
    };

    const handleAuthError = (error: unknown) => {
      const sessionWasReplaced = error instanceof AuthApiError && error.code === "STUDENT_SESSION_REPLACED";
      clearAuth();
      if (sessionWasReplaced) {
        sessionStorage.setItem(SESSION_REPLACED_KEY, "1");
        window.location.replace("/student-login?reason=session-replaced");
      }
    };

    const initializeAuth = async () => {
      if (!token) {
        setLoading(false);
        return;
      }

      try {
        const response = await getMe(token);
        if (cancelled) return;
        setUser(response.user);
        if (response.user.role === "student") {
          sessionCheckTimer = window.setInterval(async () => {
            try {
              await getMe(token);
            } catch (error) {
              if (sessionCheckTimer !== undefined) {
                window.clearInterval(sessionCheckTimer);
              }
              handleAuthError(error);
            }
          }, STUDENT_SESSION_CHECK_MS);
        }
      } catch (error) {
        if (cancelled) return;
        handleAuthError(error);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    void initializeAuth();
    return () => {
      cancelled = true;
      if (sessionCheckTimer !== undefined) {
        window.clearInterval(sessionCheckTimer);
      }
    };
  }, [token]);

  const value = useMemo<AuthContextType>(
    () => ({
      user,
      token,
      loading,
      login: async (email: string, password: string, role: "student" | "tutor") => {
        // A login attempt starts a fresh session. Do not leave a previously
        // authenticated account active when the new credentials are rejected.
        setUser(null);
        setToken(null);
        localStorage.removeItem(TOKEN_KEY);
        const response = await loginApi(email, password, role);
        setUser(response.user);
        setToken(response.token);
        localStorage.setItem(TOKEN_KEY, response.token);
      },
      register: async (name: string, email: string, password: string, role: "student" | "tutor", details: RegisterDetails = {}) => {
        const response = await registerApi(name, email, password, role, details);
        setUser(response.user);
        setToken(response.token);
        localStorage.setItem(TOKEN_KEY, response.token);
      },
      forgotPassword: async (email: string) => {
        await forgotPasswordApi(email);
      },
      logout: () => {
        setUser(null);
        setToken(null);
        localStorage.removeItem(TOKEN_KEY);
      },
    }),
    [loading, token, user],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
};
