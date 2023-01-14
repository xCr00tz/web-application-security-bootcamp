FROM golang:1.18
WORKDIR /go/src/app
COPY main.go .

RUN go mod init
RUN go get -u
RUN go mod tidy
RUN go build -o main .

EXPOSE 3000

CMD ["./main"]