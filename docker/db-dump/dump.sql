--
-- PostgreSQL database dump
--

-- Dumped from database version 10.5 (Debian 10.5-1.pgdg90+1)
-- Dumped by pg_dump version 10.5 (Debian 10.5-1.pgdg90+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: blocked_balances; Type: TABLE; Schema: public; Owner: billing
--

CREATE TABLE public.blocked_balances (
    id uuid NOT NULL,
    user_id integer NOT NULL,
    amount numeric(16,2) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.blocked_balances OWNER TO billing;

--
-- Name: transfers; Type: TABLE; Schema: public; Owner: billing
--

CREATE TABLE public.transfers (
    id integer NOT NULL,
    user_id integer NOT NULL,
    type text NOT NULL,
    from_user_id integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    operation_id uuid NOT NULL,
    amount numeric
);


ALTER TABLE public.transfers OWNER TO billing;

--
-- Name: events_id_seq; Type: SEQUENCE; Schema: public; Owner: billing
--

CREATE SEQUENCE public.events_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.events_id_seq OWNER TO billing;

--
-- Name: events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: billing
--

ALTER SEQUENCE public.events_id_seq OWNED BY public.transfers.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: billing
--

CREATE TABLE public.users (
    id integer NOT NULL,
    name text NOT NULL,
    balance numeric(16,2) DEFAULT 0 NOT NULL,
    blocked_balance numeric(16,2) DEFAULT 0 NOT NULL
);


ALTER TABLE public.users OWNER TO billing;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: billing
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO billing;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: billing
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: transfers id; Type: DEFAULT; Schema: public; Owner: billing
--

ALTER TABLE ONLY public.transfers ALTER COLUMN id SET DEFAULT nextval('public.events_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: billing
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: blocked_balances; Type: TABLE DATA; Schema: public; Owner: billing
--

COPY public.blocked_balances (id, user_id, amount, created_at) FROM stdin;
\.


--
-- Data for Name: transfers; Type: TABLE DATA; Schema: public; Owner: billing
--

COPY public.transfers (id, user_id, type, from_user_id, created_at, operation_id, amount) FROM stdin;
5876	1	change_balance	\N	2019-03-19 10:01:10.828942	7d86b5b7-d0a8-4c0c-a6b6-98633fd7c359	500
5877	1	change_balance	\N	2019-03-19 10:01:35.557658	7d86b5b7-d0a8-4c0c-a6b6-98633fd7c360	-500
5879	1	change_balance	\N	2019-03-19 10:02:03.546859	7d86b5b7-d0a8-4c0c-a6b6-98633fd7c362	500
5883	2	transfer_balance	1	2019-03-19 10:04:55.864095	7d86b5b7-d0a8-4c0c-a6b6-98633fd7c363	100
5885	2	transfer_balance	1	2019-03-19 10:05:16.742925	7d86b5b7-d0a8-4c0c-a6b6-98633fd7c364	100
5888	1	block_balance	\N	2019-03-19 10:12:52.015597	7d86b5b7-d0a8-4c0c-a6b6-98633fd7c367	55.45
5889	1	block_balance	\N	2019-03-19 10:13:17.499524	7d86b5b7-d0a8-4c0c-a6b6-98633fd7c368	100
5890	1	commit_blocked_balance	\N	2019-03-19 10:14:02.744152	7d86b5b7-d0a8-4c0c-a6b6-98633fd7c344	\N
5891	1	rollback_blocked_balance	\N	2019-03-19 10:14:51.23024	7d86b5b7-d0a8-4c0c-a6b6-98633fd7c345	\N
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: billing
--

COPY public.users (id, name, balance, blocked_balance) FROM stdin;
2	Петя	200.00	0.00
1	Вася	200.00	0.00
\.


--
-- Name: events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: billing
--

SELECT pg_catalog.setval('public.events_id_seq', 5891, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: billing
--

SELECT pg_catalog.setval('public.users_id_seq', 2, true);


--
-- Name: blocked_balances blocked_balances_pk; Type: CONSTRAINT; Schema: public; Owner: billing
--

ALTER TABLE ONLY public.blocked_balances
    ADD CONSTRAINT blocked_balances_pk PRIMARY KEY (id);


--
-- Name: transfers events_pk; Type: CONSTRAINT; Schema: public; Owner: billing
--

ALTER TABLE ONLY public.transfers
    ADD CONSTRAINT events_pk PRIMARY KEY (id);


--
-- Name: transfers events_un; Type: CONSTRAINT; Schema: public; Owner: billing
--

ALTER TABLE ONLY public.transfers
    ADD CONSTRAINT events_un UNIQUE (operation_id);


--
-- Name: users users_pk; Type: CONSTRAINT; Schema: public; Owner: billing
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pk PRIMARY KEY (id);


--
-- Name: events_from_user_id_idx; Type: INDEX; Schema: public; Owner: billing
--

CREATE INDEX events_from_user_id_idx ON public.transfers USING btree (from_user_id);


--
-- Name: events_user_id_idx; Type: INDEX; Schema: public; Owner: billing
--

CREATE INDEX events_user_id_idx ON public.transfers USING btree (user_id);


--
-- Name: blocked_balances blocked_balances_users_fk; Type: FK CONSTRAINT; Schema: public; Owner: billing
--

ALTER TABLE ONLY public.blocked_balances
    ADD CONSTRAINT blocked_balances_users_fk FOREIGN KEY (user_id) REFERENCES public.users(id) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: transfers events_users_fk; Type: FK CONSTRAINT; Schema: public; Owner: billing
--

ALTER TABLE ONLY public.transfers
    ADD CONSTRAINT events_users_fk FOREIGN KEY (user_id) REFERENCES public.users(id) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: transfers events_users_fk_1; Type: FK CONSTRAINT; Schema: public; Owner: billing
--

ALTER TABLE ONLY public.transfers
    ADD CONSTRAINT events_users_fk_1 FOREIGN KEY (from_user_id) REFERENCES public.users(id) ON UPDATE RESTRICT ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

