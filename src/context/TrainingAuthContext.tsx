import { createContext, ReactNode, useContext, useEffect, useMemo, useState } from "react";
import { trainingLogin, trainingRegister, trainingMe } from "@/services/trainingApi";

type TrainingUser = {
  id: string;
  name: string;
  email: string;
  role: "admin" | "teacher" | "student";
  approved?: boolean;
};

type TrainingAuthContextType = {
  user: TrainingUser | null;
  token: string | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, role: "teacher" | "admin") => Promise<void>;
  logout: () => void;
};

const TrainingAuthContext = createContext<TrainingAuthContextType | null>(null);
const TOKEN_KEY = "abacus_training_token";

export const TrainingAuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<TrainingUser | null>(null);
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_KEY));
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const init = async () => {
      if (!token) {
        setLoading(false);
        return;
      }
      try {
        const response = await trainingMe(token);
        setUser(response.user as TrainingUser);
      } catch {
        localStorage.removeItem(TOKEN_KEY);
        setToken(null);
        setUser(null);
      } finally {
        setLoading(false);
      }
    };
    void init();
  }, [token]);

  const value = useMemo<TrainingAuthContextType>(
    () => ({
      user,
      token,
      loading,
      login: async (email: string, password: string) => {
        const response = await trainingLogin(email, password);
        setUser(response.user);
        setToken(response.token);
        localStorage.setItem(TOKEN_KEY, response.token);
      },
      register: async (name: string, email: string, password: string, role: "teacher" | "admin") => {
        const response = await trainingRegister(name, email, password, role);
        setUser(response.user);
        setToken(response.token);
        localStorage.setItem(TOKEN_KEY, response.token);
      },
      logout: () => {
        setUser(null);
        setToken(null);
        localStorage.removeItem(TOKEN_KEY);
      },
    }),
    [loading, token, user],
  );

  return <TrainingAuthContext.Provider value={value}>{children}</TrainingAuthContext.Provider>;
};

export const useTrainingAuth = () => {
  const ctx = useContext(TrainingAuthContext);
  if (!ctx) throw new Error("useTrainingAuth must be used within TrainingAuthProvider");
  return ctx;
};
