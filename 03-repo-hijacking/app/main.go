package main

import (
        "net/http"

        "github.com/go-chi/chi/v5"
        "github.com/go-chi/chi/v5/middleware"

        "github.com/victimtesting/securelibrary"
)

func main() {
        r := chi.NewRouter()

        output_echo := SecureLibrary.Echo()

        output := "welcome "+output_echo
        r.Use(middleware.Logger)
        r.Get("/", func(w http.ResponseWriter, r *http.Request) {
                w.Write([]byte(output))
        })
        http.ListenAndServe(":3000", r)
}