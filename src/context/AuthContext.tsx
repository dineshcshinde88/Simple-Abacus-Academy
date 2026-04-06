import { createContext, ReactNode, useContext, useEffect, useMemo, useState } from "react";
import { AuthUser, forgotPassword as forgotPasswordApi, getMe, login as loginApi, register as registerApi } from "@/lib/auth";

type AuthContextType = {
  user: AuthUser | null;
  loading: boolean;
  token: string | null;
  login: (email: string, password: string, role: "student" | "tutor") => Promise<void>;
  register: (name: string, email: string, password: string, role: "student" | "tutor") => Promise<void>;
  forgotPassword: (email: string) => Promise<void>;
  logout: () => void;
};

const AuthContext = createContext<AuthContextType | null>(null);
const TOKEN_KEY = "abacus_auth_token";

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_KEY));
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const initializeAuth = async () => {
      if (!token) {
        setLoading(false);
        return;
      }

      try {
        const response = await getMe(token);
        setUser(response.user);
      } catch {
        localStorage.removeItem(TOKEN_KEY);
        setToken(null);
        setUser(null);
      } finally {
        setLoading(false);
      }
    };

    void initializeAuth();
  }, [token]);

  const value = useMemo<AuthContextType>(
    () => ({
      user,
      token,
      loading,
      login: async (email: string, password: string, role: "student" | "tutor") => {
        const response = await loginApi(email, password, role);
        setUser(response.user);
        setToken(response.token);
        localStorage.setItem(TOKEN_KEY, response.token);
      },
      register: async (name: string, email: string, password: string, role: "student" | "tutor") => {
        const response = await registerApi(name, email, password, role);
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
